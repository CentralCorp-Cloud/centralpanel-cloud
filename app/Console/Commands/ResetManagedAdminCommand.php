<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetManagedAdminCommand extends Command
{
    protected $signature = 'panel:admin-reset {--bootstrap-file= : JSON file containing email and password}';

    protected $description = 'Rotate a managed panel administrator password without interactive input';

    public function handle(): int
    {
        $path = (string) ($this->option('bootstrap-file') ?: env('PANEL_BOOTSTRAP_FILE', ''));
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if (! is_array($data) || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($password) < 12) {
            $this->components->error('A valid bootstrap JSON file with email and a 12-character password is required.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->where('is_admin', true)->first();
        if ($user === null) {
            $this->components->error('Administrator not found.');

            return self::FAILURE;
        }

        $user->forceFill(['password' => Hash::make($password)])->save();
        $this->components->info('Administrator password rotated.');

        return self::SUCCESS;
    }
}
