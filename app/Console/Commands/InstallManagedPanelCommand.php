<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PanelInstallation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class InstallManagedPanelCommand extends Command
{
    protected $signature = 'panel:install {--bootstrap-file= : JSON file containing name, email and password}';

    protected $description = 'Install a managed CentralPanel instance without interactive input';

    public function handle(): int
    {
        $bootstrapPath = (string) ($this->option('bootstrap-file') ?: env('PANEL_BOOTSTRAP_FILE', ''));

        if (PanelInstallation::isInstalled()) {
            $this->components->info('CentralPanel is already installed.');

            return self::SUCCESS;
        }

        $bootstrap = $this->readBootstrap($bootstrapPath);
        if ($bootstrap === null) {
            return self::FAILURE;
        }

        try {
            DB::connection()->getPdo();
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);

            $user = User::query()->where('email', $bootstrap['email'])->first();
            if ($user === null) {
                $user = User::query()->create([
                    'name' => $bootstrap['name'],
                    'email' => $bootstrap['email'],
                    'password' => Hash::make($bootstrap['password']),
                    'is_admin' => true,
                    'email_verified_at' => now(),
                ]);
            } elseif (! $user->is_admin) {
                $this->components->error('The bootstrap email already belongs to a non-admin user.');

                return self::FAILURE;
            }

            $this->prepareStorage();
            PanelInstallation::markInstalled($user->email);
            Artisan::call('optimize:clear');
        } catch (\Throwable $exception) {
            $this->components->error('Managed installation failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('CentralPanel installed successfully.');

        return self::SUCCESS;
    }

    /** @return array{name: string, email: string, password: string}|null */
    private function readBootstrap(string $path): ?array
    {
        if ($path === '' || ! is_file($path)) {
            $this->components->error('A readable bootstrap JSON file is required.');

            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            $this->components->error('The bootstrap file must contain valid JSON.');

            return null;
        }

        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($password) < 12) {
            $this->components->error('Bootstrap name, valid email and a password of at least 12 characters are required.');

            return null;
        }

        return compact('name', 'email', 'password');
    }

    private function prepareStorage(): void
    {
        foreach (['app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
            File::ensureDirectoryExists(storage_path($directory));
        }

        $link = public_path('storage');
        if (! is_link($link) && ! file_exists($link)) {
            File::link(storage_path('app/public'), $link);
        }
    }
}
