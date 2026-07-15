<?php

namespace Tests\Feature;

use App\Models\Instance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultiInstanceMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_description_migration_upgrades_an_existing_instances_table(): void
    {
        Schema::table('instances', function ($table) {
            $table->dropColumn('description');
        });
        $this->assertFalse(Schema::hasColumn('instances', 'description'));

        $migration = require database_path('migrations/2026_07_14_020000_add_description_to_instances_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('instances', 'description'));
    }

    public function test_role_video_migration_upgrades_existing_backgrounds(): void
    {
        Schema::table('options_bgs', function ($table) {
            $table->dropColumn('video_url');
        });
        $this->assertFalse(Schema::hasColumn('options_bgs', 'video_url'));

        $migration = require database_path('migrations/2026_07_14_030000_add_video_url_to_options_bgs_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('options_bgs', 'video_url'));
    }

    public function test_existing_configuration_and_files_are_migrated_to_default_instance(): void
    {
        $migration = require database_path('migrations/2026_07_14_000000_add_multi_instance_support.php');
        $migration->down();

        DB::table('options_server')->insert([
            'server_id' => 42,
            'server_name' => 'Legacy Server',
            'server_ip' => 'legacy.example.test',
            'server_port' => '25570',
            'type' => 'minecraft',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('options_loader')->insert([
            'minecraft_version' => '1.19.4',
            'loader_type' => 'forge',
            'loader_build_version' => '45.2.0',
            'loader_activation' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $modId = DB::table('mods')->insertGetId([
            'file' => 'legacy.jar',
            'name' => 'Legacy Mod',
            'optional' => false,
            'recommended' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        File::ensureDirectoryExists(storage_path('app/public/data'));
        File::put(storage_path('app/public/data/legacy.jar'), 'legacy-data');

        try {
            $migration->up();

            $instance = Instance::where('is_default', true)->firstOrFail();
            $this->assertTrue(Schema::hasColumn('instances', 'description'));
            $this->assertSame('Legacy Server', $instance->display_name);
            $this->assertSame('1.19.4', $instance->minecraft_version);
            $this->assertDatabaseHas('mods', ['id' => $modId, 'instance_id' => $instance->id]);
            $this->assertFileExists(storage_path('app/public/data/default/legacy.jar'));
            $this->assertFileDoesNotExist(storage_path('app/public/data/legacy.jar'));
        } finally {
            File::delete(storage_path('app/public/data/default/legacy.jar'));
        }
    }
}
