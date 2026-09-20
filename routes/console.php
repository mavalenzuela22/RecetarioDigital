<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:user-provision {email} {--name=} {--password-env=}', function (): int {
    $email = strtolower(trim((string) $this->argument('email')));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('El correo no es válido.');

        return 1;
    }

    $name = trim((string) $this->option('name'));
    if ($name === '') {
        if (! $this->input->isInteractive()) {
            $this->error('El nombre es obligatorio.');

            return 1;
        }
        $name = trim((string) $this->ask('Nombre'));
    }
    if ($name === '') {
        $this->error('El nombre es obligatorio.');

        return 1;
    }

    $passwordEnvironmentVariable = trim((string) $this->option('password-env'));
    if ($passwordEnvironmentVariable !== '') {
        $password = getenv($passwordEnvironmentVariable);
        if ($password === false || $password === '') {
            $this->error('La variable de contraseña no está disponible.');

            return 1;
        }
    } else {
        if (! $this->input->isInteractive()) {
            $this->error('La contraseña requiere una terminal interactiva o --password-env.');

            return 1;
        }
        $password = (string) $this->secret('Contraseña');
        $confirmation = (string) $this->secret('Confirma la contraseña');
        if ($password !== $confirmation) {
            $this->error('Las contraseñas no coinciden.');

            return 1;
        }
    }

    if (mb_strlen($password) < 12) {
        $this->error('La contraseña debe tener al menos 12 caracteres.');

        return 1;
    }

    User::query()->updateOrCreate(
        ['email' => $email],
        ['name' => $name, 'password' => $password],
    );

    $this->info('Usuario listo: '.$email);

    return 0;
})->purpose('Provision or update an application user');
