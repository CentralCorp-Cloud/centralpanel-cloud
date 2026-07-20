<?php

namespace App\Console\Commands;

use App\Support\AutoInstaller;
use App\Support\PanelInstallation;
use App\Support\StrictJsonFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AutoInstallCommand extends Command
{
    protected $signature = 'auto:install
        {--p|pseudo= : Pseudo du compte administrateur}
        {--m|mail= : Adresse e-mail du compte administrateur}
        {--pass= : Mot de passe du compte administrateur}
        {--bootstrap-file= : Fichier JSON strict contenant name, email et password}';

    protected $description = 'Installe automatiquement le panel et crée le compte administrateur';

    public function handle(AutoInstaller $installer): int
    {
        if (PanelInstallation::isInstalled()) {
            $this->info('Le panel est déjà installé. Aucune donnée n’a été modifiée.');

            return self::SUCCESS;
        }

        try {
            $credentials = $this->credentials();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

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
            if (PanelInstallation::isInstalled()) {
                $this->info('Le panel est déjà installé. Aucune donnée n’a été modifiée.');

                return self::SUCCESS;
            }

            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($user === null) {
            $this->info('Le panel est déjà installé. Aucune donnée n’a été modifiée.');
        } else {
            $this->newLine();
            $this->info("Installation terminée. Administrateur créé : {$user->email}");
        }

        return self::SUCCESS;
    }

    /** @return array{pseudo: mixed, mail: mixed, password: mixed} */
    private function credentials(): array
    {
        $bootstrapFile = (string) $this->option('bootstrap-file');

        if ($bootstrapFile === '') {
            return [
                'pseudo' => $this->option('pseudo'),
                'mail' => $this->option('mail'),
                'password' => $this->option('pass'),
            ];
        }

        if ($this->option('pseudo') !== null || $this->option('mail') !== null || $this->option('pass') !== null) {
            throw new \RuntimeException('Le fichier bootstrap ne peut pas être combiné avec les options historiques.');
        }

        $payload = StrictJsonFile::read($bootstrapFile, ['name', 'email', 'password']);

        return [
            'pseudo' => $payload['name'],
            'mail' => $payload['email'],
            'password' => $payload['password'],
        ];
    }
}
