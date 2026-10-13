<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateAdmin extends Command
{
    protected $signature = 'pass:create-admin {email=admin@pass.edu} {code=ADM-001}';
    protected $description = 'Create the first PASS system administrator account';

    public function handle(): int
    {
        $email = $this->argument('email');
        $code = $this->argument('code');

        if (User::withTrashed()->where('email', $email)->orWhere('user_code', $code)->exists()) {
            $this->error('A user with that email or user code already exists.');
            return self::FAILURE;
        }

        $role = Role::where('role_code', 'admin')->first();
        if (! $role) {
            $this->error('The admin role is missing. Run: php artisan db:seed');
            return self::FAILURE;
        }

        $password = $this->secret('Choose a password (min 8 characters, typing is hidden)');
        $confirm = $this->secret('Type the password again');

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }
        if ($password !== $confirm) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $code, $password, $role) {
            $user = User::create([
                'user_code' => $code,
                'email' => $email,
                'password' => $password,
                'user_type' => 'admin',
                'account_status' => 'active',
            ]);

            $user->profile()->create([
                'first_name' => 'System',
                'last_name' => 'Administrator',
            ]);

            $user->roles()->attach($role->role_id);
        });

        $this->info("Admin created: {$email} ({$code})");
        return self::SUCCESS;
    }
}