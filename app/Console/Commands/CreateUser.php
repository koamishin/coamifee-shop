<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

final class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user
                            {name : User name}
                            {email : User email}
                            {--password= : User password (auto-generated if not provided)}
                            {--admin : Create admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->option('password') ?: $this->generatePassword();
        $isAdmin = $this->option('admin');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email '{$email}' already exists!");

            return Command::FAILURE;
        }

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Assign admin role if requested
        if ($isAdmin) {
            try {
                $user->assignRole('admin');
            } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
                $this->warn('Admin role does not exist. Creating it...');
                $user->assignRole('admin');
            }
        }

        $this->info('✅ User created successfully!');
        $this->line("Name: {$name}");
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");

        if ($isAdmin) {
            $this->line('Role: Admin');
        }

        return Command::SUCCESS;
    }

    /**
     * Generate a random password.
     */
    private function generatePassword(): string
    {
        return mb_substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 8)), 0, 12);
    }
}
