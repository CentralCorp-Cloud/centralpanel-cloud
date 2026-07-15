<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsExportController extends Controller
{
    private const GLOBAL_TABLES = [
        'options_general',
        'options_ui',
        'options_server',
        'options_rpc',
        'options_security',
        'options_loader',
        'news',
    ];

    private const SCOPED_TABLES = [
        'mods',
        'ignored_folders',
        'whitelist',
        'whitelist_roles',
        'options_bgs',
    ];

    private const EXPORT_TABLES = [
        ...self::GLOBAL_TABLES,
        'instances',
        ...self::SCOPED_TABLES,
    ];

    public function export()
    {
        $data = [];
        foreach (self::EXPORT_TABLES as $table) {
            $data[$table] = Schema::hasTable($table) ? DB::table($table)->get() : [];
        }

        $settings = [
            'version' => '2.0',
            'export_date' => now()->format('Y-m-d H:i:s'),
            'data' => $data,
        ];

        $filename = 'centralcorp_settings_' . date('Y-m-d_H-i-s') . '.centralcorp';

        return response(json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function import(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:centralcorp,json|max:2048',
        ]);

        $settings = json_decode((string) file_get_contents($request->file('settings_file')->path()), true);
        if (!is_array($settings) || !isset($settings['data']) || !is_array($settings['data'])) {
            return back()->with('error', __('messages.settings_import.invalid_file'));
        }

        if (!in_array($settings['version'] ?? null, ['1.0', '2.0'], true)) {
            return back()->with('error', __('messages.settings_import.unsupported_version'));
        }

        foreach (array_keys($settings['data']) as $table) {
            if (!in_array($table, self::EXPORT_TABLES, true)) {
                return back()->with('error', __('messages.settings_import.unauthorized_table', ['table' => $table]));
            }
        }

        try {
            DB::transaction(function () use ($settings) {
                $data = $settings['data'];
                $hasInstances = !empty($data['instances']);

                foreach (self::SCOPED_TABLES as $table) {
                    if (Schema::hasTable($table) && ($hasInstances || array_key_exists($table, $data))) {
                        DB::table($table)->delete();
                    }
                }
                if ($hasInstances) {
                    DB::table('instances')->delete();
                }

                foreach (self::GLOBAL_TABLES as $table) {
                    if (array_key_exists($table, $data)) {
                        DB::table($table)->delete();
                        $this->insertRows($table, $data[$table]);
                    }
                }

                if ($hasInstances) {
                    $this->insertRows('instances', $data['instances']);
                } elseif (!DB::table('instances')->exists()) {
                    $this->createLegacyDefaultInstance();
                }

                $defaultInstanceId = DB::table('instances')->where('is_default', true)->value('id')
                    ?? DB::table('instances')->value('id');
                if ($defaultInstanceId !== null) {
                    DB::table('instances')->update(['is_default' => false]);
                    DB::table('instances')->where('id', $defaultInstanceId)->update(['is_default' => true]);
                }

                foreach (self::SCOPED_TABLES as $table) {
                    if (!array_key_exists($table, $data)) {
                        continue;
                    }

                    $rows = array_map(function ($row) use ($defaultInstanceId) {
                        $row = (array) $row;
                        $row['instance_id'] ??= $defaultInstanceId;
                        return $row;
                    }, (array) $data[$table]);
                    $this->insertRows($table, $rows);
                }
            });

            Cache::forever('launcher_options_version', (int) Cache::get('launcher_options_version', 1) + 1);
            Cache::forever('launcher_files_version', (int) Cache::get('launcher_files_version', 1) + 1);

            return back()->with('success', __('messages.settings_import.success'));
        } catch (\Throwable $e) {
            return back()->with('error', __('messages.settings_import.error', ['message' => $e->getMessage()]));
        }
    }

    private function insertRows(string $table, mixed $rows): void
    {
        if (!Schema::hasTable($table) || !is_iterable($rows)) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing($table));
        foreach ($rows as $row) {
            $filtered = array_intersect_key((array) $row, $columns);
            if ($filtered !== []) {
                DB::table($table)->insert($filtered);
            }
        }
    }

    private function createLegacyDefaultInstance(): void
    {
        $server = DB::table('options_server')->where('is_default', true)->first()
            ?? DB::table('options_server')->first();
        $loader = DB::table('options_loader')->first();

        DB::table('instances')->insert([
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
    }
}
