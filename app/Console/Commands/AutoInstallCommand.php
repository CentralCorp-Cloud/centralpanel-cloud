<?php

namespace App\Console\Commands;

use App\Support\AutoInstaller;
use App\Support\PanelInstallation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AutoInstallCommand extends Command
{
    protected $signature = 'auto:install
        {--p|pseudo= : Pseudo du compte administrateur}
        {--m|mail= : Adresse e-mail du compte administrateur}
        {--pass= : Mot de passe du compte administrateur}';

    protected $description = 'Installe automatiquement le panel et crée le compte administrateur';

    public function handle(AutoInstaller $installer): int
    {
        if (PanelInstallation::ensureInstalledState()) {
            $this->info('Le panel est déjà installé. Aucune donnée n’a été modifiée.');

            return self::SUCCESS;
        }

        $credentials = [
            'pseudo' => $this->option('pseudo'),
            'mail' => $this->option('mail'),
            'password' => $this->option('pass'),
        ];

        $validator = Validator::make($credentials, [
            'pseudo' => ['required', 'string', 'max:255'],
            'mail' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', Password::default()],
        ], [], [
            'pseudo' => 'pseudo',
            'mail' => 'adresse e-mail',
            'password' => 'mot de passe',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Installation automatique en cours…');

        try {
            $user = $installer->install(
                (string) $credentials['pseudo'],
                (string) $credentials['mail'],
                (string) $credentials['password'],
            );
        } catch (Throwable $exception) {
            $this->error('Échec de l’installation : ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Installation terminée. Administrateur créé : {$user->email}");

        return self::SUCCESS;
    }
}
