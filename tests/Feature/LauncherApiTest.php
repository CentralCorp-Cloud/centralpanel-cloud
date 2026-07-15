<?php

namespace Tests\Feature;

use App\Models\Instance;
use App\Models\OptionsGeneral;
use App\Models\OptionsBg;
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
        $instance = Instance::where('is_default', true)->firstOrFail();
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
        $instance->update([
            'display_name' => 'Lobby',
            'server_name' => 'Lobby',
            'server_ip' => '127.0.0.1',
            'server_port' => '25565',
            'minecraft_version' => '1.20.1',
            'loader_type' => 'forge',
            'loader_build_version' => '47.2.0',
            'loader_activation' => true,
        ]);
        OptionsIgnore::create(['folder_name' => 'cache', 'instance_id' => $instance->id]);
        OptionsWhitelist::create(['users' => 'Steve', 'instance_id' => $instance->id]);
        OptionsWhitelistRole::create(['role' => 'VIP', 'instance_id' => $instance->id]);

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
        $instance = Instance::where('is_default', true)->firstOrFail();
        OptionsMods::create([
            'file' => 'example.jar',
            'name' => 'Example Mod',
            'description' => 'Optional mod',
            'optional' => true,
            'recommended' => true,
            'instance_id' => $instance->id,
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
        $instance = Instance::where('is_default', true)->firstOrFail();
        Storage::disk('public')->put('data/default/codex-test.jar', 'abc');
        Storage::disk('public')->put('data/default/ignored/hidden.jar', 'hidden');
        OptionsIgnore::create(['folder_name' => 'ignored', 'instance_id' => $instance->id]);

        try {
            $response = $this->getJson('/data');

            $response->assertOk()
                ->assertJsonFragment([
                    'path' => 'codex-test.jar',
                    'size' => 3,
                    'hash' => sha1('abc'),
                    'url' => url('storage/data/default/codex-test.jar'),
                ])
                ->assertJsonMissing(['path' => 'ignored/hidden.jar']);
        } finally {
            Storage::disk('public')->delete('data/default/codex-test.jar');
            Storage::disk('public')->delete('data/default/ignored/hidden.jar');
        }
    }

    public function test_v2_contract_exposes_isolated_instances_and_scoped_files(): void
    {
        $default = Instance::where('is_default', true)->firstOrFail();
        $default->update([
            'display_name' => 'Lobby',
            'minecraft_version' => '1.20.1',
            'server_name' => 'Lobby',
        ]);
        $survival = Instance::create([
            'name' => 'survival',
            'display_name' => 'Survival',
            'description' => 'Une aventure survie communautaire.',
            'minecraft_version' => '1.21.1',
            'server_name' => 'Survival',
            'server_ip' => 'play.example.test',
            'server_port' => '25566',
            'loader_type' => 'fabric',
            'loader_build_version' => '0.16.9',
            'loader_activation' => true,
            'is_default' => false,
        ]);

        OptionsMods::create([
            'file' => 'survival.jar',
            'name' => 'Survival Mod',
            'optional' => true,
            'recommended' => false,
            'instance_id' => $survival->id,
        ]);
        OptionsWhitelist::create(['users' => 'Alex', 'instance_id' => $survival->id]);
        OptionsIgnore::create(['folder_name' => 'cache', 'instance_id' => $survival->id]);
        OptionsBg::create([
            'role_id' => '7',
            'role_name' => 'VIP',
            'image_path' => '',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'instance_id' => $survival->id,
        ]);
        Storage::disk('public')->put('data/default/lobby.jar', 'lobby');
        Storage::disk('public')->put('data/survival/mods/survival.jar', 'survival');
        Storage::disk('public')->put('data/survival/cache/ignored.bin', 'ignored');

        try {
            $options = $this->getJson('/utils/api?api_version=2');
            $options->assertOk()
                ->assertJsonCount(2, 'instances')
                ->assertJsonPath('instances.0.name', 'default')
                ->assertJsonPath('instances.1.name', 'survival')
                ->assertJsonPath('instances.1.description', 'Une aventure survie communautaire.')
                ->assertJsonPath('instances.1.role_data.role7.video_url', 'dQw4w9WgXcQ')
                ->assertJsonPath('instances.1.role_data.role7.video_type', 'video')
                ->assertJsonPath('instances.1.mods.0.file', 'survival.jar')
                ->assertJsonPath('instances.1.whitelist.0', 'Alex');

            $manifest = $this->getJson('/data?api_version=2');
            $manifest->assertOk()
                ->assertJsonFragment(['path' => 'default/lobby.jar'])
                ->assertJsonFragment(['path' => 'survival/mods/survival.jar'])
                ->assertJsonMissing(['path' => 'survival/cache/ignored.bin']);

            $legacy = $this->getJson('/data');
            $legacy->assertOk()
                ->assertJsonFragment(['path' => 'lobby.jar'])
                ->assertJsonMissing(['path' => 'survival/mods/survival.jar']);
        } finally {
            Storage::disk('public')->deleteDirectory('data/default');
            Storage::disk('public')->deleteDirectory('data/survival');
        }
    }
}
