<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\StrictJsonFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AdminResetCommand extends Command
{
    protected $signature = 'panel:admin-reset
        {--bootstrap-file= : Fichier JSON strict contenant email et password}';

    protected $description = 'Met à jour les identifiants de l’administrateur principal';

    public function handle(): int
    {
        try {
            $payload = StrictJsonFile::read(
                (string) $this->option('bootstrap-file'),
                ['email', 'password'],
                'bootstrap de reset administrateur',
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $validator = Validator::make($payload, [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', Password::default()],
        ], [], [
            'email' => 'adresse e-mail',
            'password' => 'mot de passe',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            $result = DB::transaction(function () use ($payload): string {
                $admin = User::query()
                    ->where('is_admin', true)
                    ->oldest('created_at')
                    ->oldest('id')
                    ->lockForUpdate()
                    ->first();

                if ($admin === null) {
                    return 'missing';
                }

                $conflict = User::query()
                    ->where('email', $payload['email'])
                    ->whereKeyNot($admin->getKey())
                    ->exists();

                if ($conflict) {
                    return 'conflict';
                }

                $admin->forceFill([
                    'email' => $payload['email'],
                    'password' => $payload['password'],
                    'email_verified_at' => now(),
                ])->save();

                return 'updated';
            });
        } catch (Throwable) {
            $this->error('Échec du reset administrateur sans modification volontaire des autres comptes.');

            return self::FAILURE;
        }

        if ($result === 'missing') {
            $this->error('Aucun administrateur existant n’a été trouvé.');

            return self::FAILURE;
        }

        if ($result === 'conflict') {
            $this->error('Cette adresse e-mail est déjà utilisée par un autre utilisateur.');

            return self::FAILURE;
        }

        $this->info('Les identifiants de l’administrateur principal ont été mis à jour.');

        return self::SUCCESS;
    }
}
