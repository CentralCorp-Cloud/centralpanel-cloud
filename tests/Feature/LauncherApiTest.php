<?php

namespace Tests\Feature;

use App\Models\OptionsGeneral;
use App\Models\OptionsIgnore;
use App\Models\OptionsLoader;
use App\Models\OptionsMods;
use App\Models\OptionsRPC;
use App\Models\OptionsSecurity;
use App\Models\OptionsServer;
use App\Models\OptionsUI;
use App\Models\OptionsWhitelist;
use App\Models\OptionsWhitelistRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LauncherApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear();
    }

    public function test_launcher_options_contract_is_stable(): void
    {
        OptionsGeneral::create([
            'email_verified' => true,
            'azuriom_url' => 'https://azuriom.example.test',
            'mods_enabled' => true,
            'file_verification' => true,
            'embedded_java' => false,
            'game_folder_name' => 'centralcorp',
            'role_display' => true,
            'money_display' => false,
            'min_ram' => 2048,
            'max_ram' => 4096,
        ]);
        OptionsSecurity::create([
            'maintenance' => false,
            'whitelist' => true,
            'maintenance_message' => 'Maintenance',
        ]);
        OptionsUI::create([
            'alert_activation' => true,
            'alert_scroll' => false,
            'alert_msg' => 'News',
            'video_activation' => true,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'splash' => 'Splash',
            'splash_author' => 'Admin',
            'accent_color' => '#336699',
        ]);
        OptionsRPC::create([
            'rpc_activation' => true,
            'rpc_id' => '123',
            'rpc_details' => 'Details',
            'rpc_state' => 'State',
            'rpc_large_text' => 'Large',
            'rpc_small_text' => 'Small',
        ]);
        OptionsLoader::create([
            'minecraft_version' => '1.20.1',
            'loader_type' => 'forge',
            'loader_build_version' => '47.2.0',
            'loader_activation' => true,
        ]);
        OptionsServer::create([
            'server_id' => 1,
            'server_name' => 'Lobby',
            'server_ip' => '127.0.0.1',
            'server_port' => '25565',
            'type' => 'minecraft',
            'is_default' => true,
        ]);
        OptionsIgnore::create(['folder_name' => 'cache']);
        OptionsWhitelist::create(['users' => 'Steve']);
        OptionsWhitelistRole::create(['role' => 'VIP']);

        $response = $this->getJson('/utils/api');

        $response->assertOk()
            ->assertJsonPath('game_version', '1.20.1')
            ->assertJsonPath('status.nameServer', 'Lobby')
            ->assertJsonPath('loader.type', 'forge')
            ->assertJsonPath('ram_min', 2)
            ->assertJsonPath('ram_max', 4)
            ->assertJsonPath('video_url', 'dQw4w9WgXcQ')
            ->assertJsonPath('whitelist.0', 'Steve')
            ->assertJsonPath('whitelist_roles.0', 'VIP')
            ->assertJsonStructure([
                'maintenance',
                'maintenance_message',
                'game_version',
                'verify',
                'modde',
                'java',
                'dataDirectory',
                'status' => ['nameServer', 'ip', 'port'],
                'loader' => ['type', 'build', 'enable'],
                'rpc_activation',
                'alert_activate',
                'video_type',
                'role_data',
                'ignored',
                'whitelist',
                'whitelist_roles',
            ]);
    }

    public function test_optional_mods_contract_is_stable(): void
    {
        OptionsMods::create([
            'file' => 'example.jar',
            'name' => 'Example Mod',
            'description' => 'Optional mod',
            'optional' => true,
            'recommended' => true,
        ]);

        $response = $this->getJson('/utils/mods');

        $response->assertOk()
            ->assertJsonPath('optionalMods.0', 'example.jar')
            ->assertJsonFragment([
                'example.jar' => [
                    'name' => 'Example Mod',
                    'description' => 'Optional mod',
                    'icon' => '',
                    'recommanded' => true,
                ],
            ]);
    }

    public function test_file_manifest_skips_ignored_paths_and_includes_hashes(): void
    {
        Storage::disk('public')->put('data/codex-test.jar', 'abc');
        Storage::disk('public')->put('data/ignored/hidden.jar', 'hidden');
        OptionsIgnore::create(['folder_name' => 'ignored']);

        try {
            $response = $this->getJson('/data');

            $response->assertOk()
                ->assertJsonFragment([
                    'path' => 'codex-test.jar',
                    'size' => 3,
                    'hash' => sha1('abc'),
                    'url' => url('storage/data/codex-test.jar'),
                ])
                ->assertJsonMissing(['path' => 'ignored/hidden.jar']);
        } finally {
            Storage::disk('public')->delete('data/codex-test.jar');
            Storage::disk('public')->delete('data/ignored/hidden.jar');
        }
    }
}
