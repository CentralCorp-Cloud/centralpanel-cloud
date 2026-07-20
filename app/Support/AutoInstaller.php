<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

final class AutoInstaller
{
    /**
     * @return User|null The created administrator, or null when an installation already exists.
     */
    public function install(string $name, string $email, string $password): ?User
    {
        return $this->withInstallationLock(function () use ($name, $email, $password): ?User {
            $stage = 'préparation du stockage';

            try {
                $this->prepareFilesystem();
                $stage = 'connexion à la base';
                $this->prepareDatabase();
                DB::connection()->getPdo();

                $stage = 'vérification de l’état existant';
                if (PanelInstallation::ensureInstalledState() || PanelInstallation::hasMarker()) {
                    return null;
                }

                $stage = 'migration de la base';
                $this->assertDatabaseCanBeInstalled();
                Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);

                $stage = 'revérification après migration';
                if (PanelInstallation::databaseHasUsers()) {
                    PanelInstallation::ensureInstalledState();

                    return null;
                }

                $stage = 'création de l’administrateur';
                $user = DB::transaction(fn (): User => User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'is_admin' => true,
                    'email_verified_at' => now(),
                ]));

                $stage = 'finalisation de la configuration';
                $this->configureApplication();
                PanelInstallation::markInstalled($user->email);

                return $user;
            } catch (Throwable $exception) {
                throw new RuntimeException("Échec sécurisé à l’étape : {$stage}.", 0, $exception);
            }
        });
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
            (string) config('centralpanel.runtime_path', storage_path('runtime')),
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

        $configuredPath = (string) config('database.connections.sqlite.database');
        $path = DatabasePath::ensureSqliteFile($configuredPath);

        if ($path === $configuredPath) {
            return;
        }

        Config::set('database.connections.sqlite.database', $path);
        DB::purge('sqlite');
    }

    private function assertDatabaseCanBeInstalled(): void
    {
        $tables = DB::connection()->getSchemaBuilder()->getTableListing();

        if ($tables === []) {
            return;
        }

        $tableNames = array_map(
            static fn (string $table): string => str_contains($table, '.')
                ? substr($table, strrpos($table, '.') + 1)
                : $table,
            $tables,
        );

        if (!in_array('migrations', $tableNames, true) && !in_array('users', $tableNames, true)) {
            throw new RuntimeException('La base configurée n’est pas une base CentralPanel vierge ou reprenable.');
        }
    }

    private function configureApplication(): void
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $values = [
            'APP_NAME' => 'CentralCorp Panel',
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
            'APP_URL' => $appUrl,
            'LOG_LEVEL' => 'error',
        ];

        if (!config('centralpanel.managed', false) && (string) env('APP_KEY_FILE', '') === '') {
            $appKey = 'base64:' . base64_encode(
                Encrypter::generateKey((string) config('app.cipher', 'AES-256-CBC'))
            );
            $values['APP_KEY'] = $appKey;
            Config::set('app.key', $appKey);
        } elseif (!PanelInstallation::hasRealAppKey()) {
            throw new RuntimeException('La clé applicative fournie est absente ou invalide.');
        }

        DotenvEditor::updateFile(base_path('.env'), $values);
        Config::set('app.url', $appUrl);
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    private function withInstallationLock(callable $callback): mixed
    {
        $runtimePath = (string) config('centralpanel.runtime_path', storage_path('runtime'));
        if (!is_dir($runtimePath) && !@mkdir($runtimePath, 0755, true) && !is_dir($runtimePath)) {
            throw new RuntimeException('Le répertoire du verrou d’installation ne peut pas être créé.');
        }
        $lockPath = $runtimePath . DIRECTORY_SEPARATOR . 'auto-install.lock';
        $lock = @fopen($lockPath, 'c+');

        if ($lock === false) {
            throw new RuntimeException('Le verrou d’installation ne peut pas être ouvert.');
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Le verrou d’installation ne peut pas être acquis.');
            }

            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
