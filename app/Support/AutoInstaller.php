<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

final class AutoInstaller
{
    public function install(string $name, string $email, string $password): User
    {
        $this->prepareFilesystem();
        $this->prepareDatabase();

        DB::connection()->getPdo();
        Artisan::call('migrate:fresh', ['--force' => true]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        try {
            Artisan::call('storage:link', ['--force' => true]);
        } catch (Throwable) {
            // The link can already exist or be managed by the container host.
        }

        $appKey = 'base64:' . base64_encode(
            Encrypter::generateKey((string) config('app.cipher', 'AES-256-CBC'))
        );
        $appUrl = (string) config('app.url', 'http://localhost');

        DotenvEditor::updateFile(base_path('.env'), [
            'APP_NAME' => 'CentralCorp Panel',
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => false,
            'APP_URL' => $appUrl,
            'LOG_LEVEL' => 'error',
        ]);

        Config::set('app.key', $appKey);
        Config::set('app.url', $appUrl);
        PanelInstallation::markInstalled($user->email);

        return $user;
    }

    private function prepareFilesystem(): void
    {
        foreach ([
            storage_path('app/public'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $directory) {
            File::ensureDirectoryExists($directory, 0755, true);
        }

        $logPath = storage_path('logs/laravel.log');
        if (!File::exists($logPath)) {
            File::put($logPath, '');
        }
    }

    private function prepareDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = DatabasePath::ensureSqliteFile(config('database.connections.sqlite.database'));
        Config::set('database.connections.sqlite.database', $path);
        DB::purge('sqlite');
    }
}
