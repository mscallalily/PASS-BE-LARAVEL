<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ParkingStaffDemoSeeder extends Seeder
{
    public function run(): void
    {
        // This demo account must never be seeded in production.
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Parking Staff demo seeder is only allowed in local/testing environments.'
            );
        }

        $email = 'security.demo@pass.local';
        $userCode = 'SEC-DEMO-001';
        $password = env('PASS_DEMO_SECURITY_PASSWORD');

        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException(
                'Set PASS_DEMO_SECURITY_PASSWORD in your local .env. Use at least 12 characters.'
            );
        }

        DB::transaction(function () use ($email, $userCode, $password) {
            // Never modify an existing account.
            if (User::withTrashed()->where('email', $email)->exists()) {
                $this->command->warn(
                    'The demo email already exists. No account details were changed.'
                );

                return;
            }

            if (User::withTrashed()->where('user_code', $userCode)->exists()) {
                throw new RuntimeException(
                    'SEC-DEMO-001 is already assigned to another account. No account was created.'
                );
            }

            $role = DB::table('roles')
                ->where('role_code', 'security')
                ->where('is_active', true)
                ->first();

            if (! $role) {
                throw new RuntimeException(
                    'The active security role was not found. Run RoleSeeder first.'
                );
            }

            $department = DB::table('departments')
                ->where('department_code', 'SEC')
                ->where('is_active', true)
                ->first();

            $userId = DB::table('users')->insertGetId([
                'user_code' => $userCode,
                'email' => $email,
                'password' => Hash::make($password),
                'user_type' => 'security',
                'account_status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'user_id');

            DB::table('user_profiles')->insert([
                'user_id' => $userId,
                'department_id' => $department?->department_id,
                'first_name' => 'Demo',
                'last_name' => 'Parking Staff',
                'program_or_position' => 'Parking Staff',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $role->role_id,
                'assigned_at' => now(),
                'assigned_by' => null,
            ]);

            $this->command->info(
                'Demo Parking Staff account created successfully.'
            );

            $this->command->line('Email: ' . $email);
            $this->command->line('User code: ' . $userCode);
            $this->command->line(
                'Password: use the value of PASS_DEMO_SECURITY_PASSWORD in your local .env'
            );
        });
    }
}
