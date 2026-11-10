<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an admin account with a hashed password';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->ask('Admin email')));
        $password = (string) $this->secret('Password', false);
        $confirmation = (string) $this->secret('Confirm password', false);

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        if (strlen($password) > 72 || str_contains($password, "\0")) {
            $this->error('Use a password of at most 72 bytes with no null characters.');

            return self::FAILURE;
        }

        try {
            Admin::create([
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->error('An admin with that email already exists.');

            return self::FAILURE;
        }

        $this->info('Admin account created.');

        return self::SUCCESS;
    }
}
