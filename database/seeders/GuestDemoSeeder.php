<?php

namespace Database\Seeders;

use App\Http\Controllers\Api\VisitorAccessController;
use App\Models\User;
use App\Services\Parking\VisitorRequestService;
use App\Support\CampusTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Development-only guest account for testing the visitor workflow:
 *   Maria Santos · guest.demo@pass.test · GuestDemo@2026!
 * with a linked visitor profile, car GUEST 001, and one PENDING visitor
 * parking request (no QR pass: an Administrator must approve it).
 *
 * Safe to rerun: an existing account is never modified, and the vehicle and
 * pending request are created only when missing.
 */
class GuestDemoSeeder extends Seeder
{
    private const EMAIL = 'guest.demo@pass.test';
    private const PASSWORD = 'GuestDemo@2026!';
    private const PLATE = 'GUEST 001';

    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('GuestDemoSeeder is only allowed in local/testing environments.');
        }

        $roleId = DB::table('roles')->where('role_code', 'guest')->where('is_active', true)->value('role_id');
        if (!$roleId) {
            throw new RuntimeException('The guest role is missing. Run php artisan migrate first.');
        }

        DB::transaction(function () use ($roleId) {
            $user = User::withTrashed()->where('email', self::EMAIL)->first();

            if (!$user) {
                $userId = DB::table('users')->insertGetId([
                    'user_code' => 'GST-DEMO-001',
                    'email' => self::EMAIL,
                    'password' => Hash::make(self::PASSWORD),
                    'user_type' => 'other',
                    'account_status' => 'active',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'user_id');
                DB::table('user_profiles')->insert([
                    'user_id' => $userId, 'first_name' => 'Maria', 'last_name' => 'Santos', 'contact_number' => '09171230001',
                    'program_or_position' => 'Guest / Visitor', 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('user_roles')->insert(['user_id' => $userId, 'role_id' => $roleId, 'assigned_at' => now()]);
                $user = User::find($userId);
                $this->command?->info('Guest demo account created: ' . self::EMAIL);
            } elseif (!$user->isGuest()) {
                throw new RuntimeException(self::EMAIL . ' belongs to a non-guest account. Nothing was changed.');
            } else {
                $this->command?->warn('Guest demo account already exists; account details were not changed.');
            }

            $visitorId = DB::table('visitors')->where('user_id', $user->user_id)->value('visitor_id');
            if (!$visitorId) {
                $visitorId = DB::table('visitors')->insertGetId([
                    'user_id' => $user->user_id, 'first_name' => 'Maria', 'last_name' => 'Santos', 'contact_number' => '09171230001',
                    'email' => self::EMAIL, 'is_blacklisted' => false, 'created_at' => now(), 'updated_at' => now(),
                ], 'visitor_id');
            }

            $sedan = DB::table('vehicle_types')->where('type_code', 'sedan')->value('vehicle_type_id');
            $vehicleId = VisitorAccessController::registerVisitorVehicle((int) $visitorId, self::PLATE, (int) $sedan, ['make' => 'Toyota', 'model' => 'Vios', 'color' => 'White']);

            // One pending request whose visit window is still open.
            $hasOpenPending = DB::table('visitor_requests')->where('vehicle_id', $vehicleId)->where('request_status', 'pending')
                ->where('requested_valid_until', '>', now()->toDateTimeString())->exists();

            if ($hasOpenPending) {
                $this->command?->warn('A pending request for ' . self::PLATE . ' already exists; none added.');

                return;
            }

            $from = CampusTime::now()->startOfHour();
            $requestId = app(VisitorRequestService::class)->create(
                (int) $visitorId, $vehicleId, 'Campus visit (demo request)', 'Admissions Office', $from, $from->addHours(8), $user, 'demo_seeder'
            );
            $this->command?->info("Pending visitor request #{$requestId} created for " . self::PLATE . ' (' . CampusTime::label($from) . ' – ' . CampusTime::label($from->addHours(8)) . ').');
        });
    }
}
