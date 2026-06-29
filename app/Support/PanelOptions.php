<?php

namespace App\Support;

use App\Models\OptionsGeneral;
use App\Models\OptionsLoader;
use App\Models\OptionsRPC;
use App\Models\OptionsSecurity;
use App\Models\OptionsUI;

class PanelOptions
{
    public static function general(): OptionsGeneral
    {
        return OptionsGeneral::firstOrCreate([], [
            'mods_enabled' => true,
            'file_verification' => true,
            'embedded_java' => false,
            'game_folder_name' => 'centralcorp',
            'email_verified' => false,
            'role_display' => true,
            'money_display' => false,
            'min_ram' => 2048,
            'max_ram' => 4096,
        ]);
    }

    public static function ui(): OptionsUI
    {
        return OptionsUI::firstOrCreate([], [
            'alert_activation' => true,
            'alert_scroll' => false,
            'alert_msg' => 'Bienvenue sur le launcher',
            'video_activation' => false,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'splash' => 'Ceci est du code',
            'splash_author' => 'Riptiaz',
            'accent_color' => '#FFA500',
        ]);
    }

    public static function security(): OptionsSecurity
    {
        return OptionsSecurity::firstOrCreate([], [
            'maintenance' => false,
            'maintenance_message' => 'Maintenance en cours.',
            'whitelist' => false,
        ]);
    }

    public static function rpc(): OptionsRPC
    {
        return OptionsRPC::firstOrCreate([], [
            'rpc_activation' => true,
            'rpc_id' => '1144257170561581097',
            'rpc_details' => 'Dans le launcher',
            'rpc_state' => 'En exploration',
            'rpc_large_text' => 'Minecraft',
            'rpc_small_text' => 'Multiplayer server',
            'rpc_button1' => 'Discord',
            'rpc_button1_url' => 'https://discord.gg/VCmNXHvf77',
            'rpc_button2' => 'Site Web',
            'rpc_button2_url' => 'https://conflictura.eu',
        ]);
    }

    public static function loader(): OptionsLoader
    {
        return OptionsLoader::firstOrCreate([], [
            'minecraft_version' => '1.20.1',
            'loader_activation' => true,
            'loader_type' => 'forge',
            'loader_forge_version' => null,
            'loader_build_version' => null,
        ]);
    }
}
