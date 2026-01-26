<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class InitCommand extends Command
{
    protected $signature = 'app:init';

    protected $description = 'Initialize the application by creating the first user';

    public function handle(): int
    {
        if (User::query()->exists()) {
            error('A user already exists. This command can only be run on a fresh installation.');

            return self::FAILURE;
        }

        info('Welcome! Let\'s create your first user account.');

        $name = text(
            label: 'What is your name?',
            required: true,
        );

        $email = text(
            label: 'What is your email?',
            required: true,
            validate: fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
                default => null,
            },
        );

        $password = password(
            label: 'Create a password',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) < 8 => 'Password must be at least 8 characters.',
                default => null,
            },
        );

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        info('User created successfully! You can now log in.');

        return self::SUCCESS;
    }
}
