<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class PanelInstallation
{
    public const TEMP_KEY = 'base64:hmU1T3OuvHdi5t1wULI8Xp7geI+JIWGog9pBCNxslY8=';

    public static function isInstalled(): bool
    {
        return self::hasMarker() && self::hasRealAppKey();
    }

    public static function ensureInstalledState(): bool
    {
        if (self::isInstalled()) {
            return true;
        }

        if (!self::hasMarker() && !self::databaseHasUsers()) {
            return false;
        }

        self::ensureRealAppKey();
        self::markInstalled();

        return self::isInstalled();
    }

    public static function markInstalled(?string $adminEmail = null): void
    {
        $content = 'Installation completed on ' . now()->format('Y-m-d H:i:s');

        if ($adminEmail) {
            $content .= "\nAdmin: {$adminEmail}";
        }

        File::put(storage_path('installed'), $content);
    }

    public static function hasRealAppKey(): bool
    {
        $key = trim((string) config('app.key'));

        return $key !== '' && $key !== self::TEMP_KEY;
    }

    public static function hasMarker(): bool
    {
        $path = storage_path('installed');

        return File::isFile($path) && File::size($path) > 0;
    }

    public static function databaseHasUsers(): bool
    {
        try {
            $schema = DB::connection()->getSchemaBuilder();

            return $schema->hasTable('users') && DB::table('users')->exists();
        } catch (\Throwable) {
            Log::debug('Unable to inspect panel installation database state.');

            return false;
        }
    }

    private static function ensureRealAppKey(): void
    {
        if (self::hasRealAppKey()) {
            return;
        }

        if ((string) env('APP_KEY_FILE', '') !== '') {
            return;
        }

        $key = 'base64:' . base64_encode(Encrypter::generateKey(config('app.cipher', 'AES-256-CBC')));
        Config::set('app.key', $key);

        if (!File::exists(base_path('.env'))) {
            return;
        }

        try {
            DotenvEditor::updateFile(base_path('.env'), ['APP_KEY' => $key]);
        } catch (\Throwable) {
            Log::warning('Unable to repair APP_KEY in .env.');
        }
    }
}
