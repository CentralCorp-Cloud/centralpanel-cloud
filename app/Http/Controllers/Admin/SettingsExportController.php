<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OptionsGeneral;
use App\Models\OptionsIgnore;
use App\Models\OptionsLoader;
use App\Models\OptionsRPC;
use App\Models\OptionsSecurity;
use App\Models\OptionsServer;
use App\Models\OptionsUI;
use App\Models\OptionsWhitelist;
use App\Models\OptionsWhitelistRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsExportController extends Controller
{
    private const EXPORT_TABLES = [
        'options_general',
        'options_ui',
        'options_server',
        'options_rpc',
        'options_security',
        'options_loader',
        'ignored_folders',
        'whitelist',
        'whitelist_roles',
    ];

    public function export()
    {
        $settings = [
            'version' => '1.0',
            'export_date' => now()->format('Y-m-d H:i:s'),
            'data' => [
                'options_general' => OptionsGeneral::all(),
                'options_ui' => OptionsUI::all(),
                'options_server' => OptionsServer::all(),
                'options_rpc' => OptionsRPC::all(),
                'options_security' => OptionsSecurity::all(),
                'options_loader' => OptionsLoader::all(),
                'ignored_folders' => OptionsIgnore::all(),
                'whitelist' => OptionsWhitelist::all(),
                'whitelist_roles' => OptionsWhitelistRole::all(),
            ],
        ];

        $json = json_encode($settings, JSON_PRETTY_PRINT);
        $filename = 'centralcorp_settings_' . date('Y-m-d_H-i-s') . '.centralcorp';

        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function import(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:centralcorp,json|max:2048',
        ]);

        $settings = json_decode(file_get_contents($request->file('settings_file')->path()), true);

        if (!$settings || !isset($settings['data'])) {
            return back()->with('error', 'Le fichier .centralcorp est invalide ou corrompu.');
        }

        if (($settings['version'] ?? null) !== '1.0') {
            return back()->with('error', 'Version du fichier .centralcorp non supportée.');
        }

        try {
            DB::transaction(function () use ($settings) {
                foreach ($settings['data'] as $table => $data) {
                    if (!in_array($table, self::EXPORT_TABLES, true)) {
                        throw new \Exception("La table {$table} n'est pas autorisée dans cet import.");
                    }

                    if (!Schema::hasTable($table)) {
                        throw new \Exception("La table {$table} n'existe pas.");
                    }

                    DB::table($table)->delete();

                    foreach ($data as $row) {
                        unset($row['created_at'], $row['updated_at']);
                        DB::table($table)->insert((array) $row);
                    }
                }
            });

            return back()->with('success', 'Les paramètres ont été importés avec succès depuis le fichier .centralcorp.');
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de l\'importation : ' . $e->getMessage());
        }
    }
}
