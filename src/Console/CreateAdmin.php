<?php

namespace IlBronza\AccountManager\Console;

use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'accountmanager:create-admin {email} {--name=}';
    protected $description = 'Create a new administrator using an interactively supplied password';

    public function handle(): int
    {
        $model = User::gpc();
        $email = $this->argument('email');
        $password = $this->secret('Password');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make([
            'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation,
        ], [
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'confirmed', Password::min(12)],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return self::FAILURE;
        }
        if ($model::withInactive()->withTrashed()->where('email', $email)->exists()) {
            $this->error('An account with this email already exists.');
            return self::FAILURE;
        }

        (new $model)->getConnection()->transaction(function () use ($model, $email, $password) {
            $user = new $model;
            $user->name = $this->option('name') ?: $email;
            $user->email = $email;
            $user->password = $password;
            $user->active = true;
            $user->save();
            $user->assignRole(Role::gpc()::findOrCreate('superadmin', 'web'));
        });
        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
