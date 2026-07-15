<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('options_general', function (Blueprint $table) {
            $table->string('auth_mode', 20)->default('azuriom');
            $table->string('news_mode', 20)->default('rss');
            $table->string('news_rss_url', 500)->nullable();
        });

        Schema::create('instances', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('server_ip')->nullable();
            $table->string('server_port')->nullable();
            $table->string('server_name')->nullable();
            $table->string('server_icon')->nullable();
            $table->string('server_icon_url')->nullable();
            $table->string('minecraft_version', 50)->nullable();
            $table->string('loader_type', 50)->nullable();
            $table->string('loader_build_version', 100)->nullable();
            $table->boolean('loader_activation')->default(true);
            $table->string('background_default')->nullable();
            $table->string('rpc_details_override')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });

        $server = DB::table('options_server')->where('is_default', true)->first()
            ?? DB::table('options_server')->first();
        $loader = DB::table('options_loader')->first();

        $instanceId = DB::table('instances')->insertGetId([
            'name' => 'default',
            'display_name' => $server->server_name ?? 'Default',
            'server_ip' => $server->server_ip ?? null,
            'server_port' => $server->server_port ?? null,
            'server_name' => $server->server_name ?? 'Default',
            'server_icon' => $server->icon_local ?? null,
            'server_icon_url' => $server->icon ?? null,
            'minecraft_version' => $loader->minecraft_version ?? null,
            'loader_type' => $loader->loader_type ?? null,
            'loader_build_version' => $loader->loader_build_version ?? $loader->loader_forge_version ?? null,
            'loader_activation' => $loader->loader_activation ?? true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['mods', 'whitelist', 'whitelist_roles', 'ignored_folders', 'options_bgs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('instance_id')->nullable()->index();
            });

            DB::table($tableName)->whereNull('instance_id')->update(['instance_id' => $instanceId]);

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('instance_id')->references('id')->on('instances')->cascadeOnDelete();
            });
        }

        $this->moveLegacyDataIntoDefaultInstance();
    }

    public function down(): void
    {
        $this->restoreDefaultInstanceData();

        foreach (['mods', 'whitelist', 'whitelist_roles', 'ignored_folders', 'options_bgs'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'instance_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['instance_id']);
                $table->dropIndex(['instance_id']);
                $table->dropColumn('instance_id');
            });
        }

        Schema::dropIfExists('instances');

        Schema::table('options_general', function (Blueprint $table) {
            $table->dropColumn(['auth_mode', 'news_mode', 'news_rss_url']);
        });
    }

    private function moveLegacyDataIntoDefaultInstance(): void
    {
        $root = storage_path('app/public/data');
        $target = $root . DIRECTORY_SEPARATOR . 'default';

        File::ensureDirectoryExists($root);
        File::ensureDirectoryExists($target);

        foreach (File::directories($root) as $directory) {
            if ($directory === $target) {
                continue;
            }

            $destination = $target . DIRECTORY_SEPARATOR . basename($directory);
            if (!File::exists($destination)) {
                File::moveDirectory($directory, $destination);
            }
        }

        foreach (File::files($root) as $file) {
            $destination = $target . DIRECTORY_SEPARATOR . $file->getFilename();
            if (!File::exists($destination)) {
                File::move($file->getPathname(), $destination);
            }
        }
    }

    private function restoreDefaultInstanceData(): void
    {
        $root = storage_path('app/public/data');
        $source = $root . DIRECTORY_SEPARATOR . 'default';

        if (!File::isDirectory($source)) {
            return;
        }

        foreach (File::directories($source) as $directory) {
            $destination = $root . DIRECTORY_SEPARATOR . basename($directory);
            if (!File::exists($destination)) {
                File::moveDirectory($directory, $destination);
            }
        }

        foreach (File::files($source) as $file) {
            $destination = $root . DIRECTORY_SEPARATOR . $file->getFilename();
            if (!File::exists($destination)) {
                File::move($file->getPathname(), $destination);
            }
        }

        if (count(File::allFiles($source)) === 0 && count(File::directories($source)) === 0) {
            File::deleteDirectory($source);
        }
    }
};
