<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Development-only Administrator account (approves visitor parking requests).
 * Same rules as ParkingStaffDemoSeeder: local/testing only, password from
 * PASS_DEMO_ADMIN_PASSWORD, and an existing account is never modified.
 */
class AdminDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Admin demo seeder is only allowed in local/testing environments.');
        }

        $email = 'admin.demo@pass.local';
        $userCode = 'ADM-DEMO-001';
        $password = env('PASS_DEMO_ADMIN_PASSWORD');

        if (!is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set PASS_DEMO_ADMIN_PASSWORD in your local .env. Use at least 12 characters.');
        }

        DB::transaction(function () use ($email, $userCode, $password) {
            if (User::withTrashed()->where('email', $email)->exists()) {
                $this->command?->warn('The demo admin email already exists. No account details were changed.');

                return;
            }
            if (User::withTrashed()->where('user_code', $userCode)->exists()) {
                throw new RuntimeException("{$userCode} is already assigned to another account. No account was created.");
            }

            $roleId = DB::table('roles')->where('role_code', 'admin')->where('is_active', true)->value('role_id');
            if (!$roleId) {
                throw new RuntimeException('The active admin role was not found. Run RoleSeeder first.');
            }

            $userId = DB::table('users')->insertGetId([
                'user_code' => $userCode,
                'email' => $email,
                'password' => Hash::make($password),
                'user_type' => 'admin',
                'account_status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'user_id');

            DB::table('user_profiles')->insert([
                'user_id' => $userId, 'first_name' => 'Demo', 'last_name' => 'Administrator',
                'program_or_position' => 'PASS Administrator', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('user_roles')->insert(['user_id' => $userId, 'role_id' => $roleId, 'assigned_at' => now()]);

            $this->command?->info("Demo Administrator created: {$email} ({$userCode}). Password: PASS_DEMO_ADMIN_PASSWORD in .env");
        });
    }
}
