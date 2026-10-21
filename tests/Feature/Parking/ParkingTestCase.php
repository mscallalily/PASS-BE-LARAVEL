<?php

namespace Tests\Feature\Parking;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Parking feature tests run on the separate projectsystem_testing database
 * (phpunit.xml), migrated and seeded with the parking configuration
 * (roles, vehicle types, the two areas with 55 + 84 slots, violation types).
 * Each test runs in a transaction that is rolled back.
 */
abstract class ParkingTestCase extends TestCase
{
    use DatabaseTransactions;

    private static int $plateSequence = 1000;

    /** Reserve distinct fixture values and skip existing/archived separator aliases. */
    protected function nextTestPlate(string $prefix): string
    {
        do {
            $plate = strtoupper($prefix) . ' ' . self::$plateSequence++;
        } while (DB::table('vehicles')->whereRaw(
            "REPLACE(REPLACE(UPPER(plate_number), '-', ''), ' ', '') = ?",
            [\App\Support\PlateNumber::key($plate)]
        )->exists());

        return $plate;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $database = DB::connection()->getDatabaseName();
        if (!str_ends_with($database, '_testing')) {
            throw new RuntimeException("Refusing to run parking tests against '{$database}'. Use the *_testing database.");
        }

        if (DB::table('parking_slots')->count() !== 139) {
            throw new RuntimeException('Seed the test database first: RoleSeeder, VehicleTypeSeeder, ParkingAreaSeeder, ViolationTypeSeeder.');
        }
    }

    protected function user(string $type = 'student', array $overrides = []): User
    {
        $code = strtoupper($type) . '-' . Str::upper(Str::random(6));
        $id = DB::table('users')->insertGetId($overrides + [
            'user_code' => $code,
            'email' => strtolower($code) . '@test.pass',
            'password' => bcrypt('password'),
            'user_type' => $type,
            'account_status' => 'active',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'user_id');

        return User::findOrFail($id);
    }

    protected function actingAsStaff(): User
    {
        $staff = $this->user('security');
        Sanctum::actingAs($staff);

        return $staff;
    }

    protected function actingAsAdmin(): User
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        return $admin;
    }

    /**
     * A campus user's vehicle with an approved authorization, a driver
     * record, a license, and an active RFID tag.
     *
     * @return array{user: User, vehicle_id: int, plate: string, tag: string, driver_id: int}
     */
    protected function campusVehicle(string $typeCode = 'sedan', string $licenseVerification = 'verified', string $authorization = 'approved', string $userType = 'student'): array
    {
        $user = $this->user($userType);
        $plate = $this->nextTestPlate('TST');
        $vehicleId = DB::table('vehicles')->insertGetId([
            'owner_user_id' => $user->user_id,
            'vehicle_type_id' => DB::table('vehicle_types')->where('type_code', $typeCode)->value('vehicle_type_id'),
            'plate_number' => $plate,
            'registration_status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vehicle_id');

        DB::table('vehicle_authorizations')->insert([
            'vehicle_id' => $vehicleId,
            'authorization_status' => $authorization,
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'user_id' => $user->user_id, 'first_name' => 'Test', 'last_name' => 'Driver', 'driver_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ], 'driver_id');
        DB::table('vehicle_driver')->insert(['vehicle_id' => $vehicleId, 'driver_id' => $driverId, 'relationship' => 'owner', 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('driver_licenses')->insert([
            'driver_id' => $driverId,
            'license_number' => 'N' . random_int(10, 99) . '-' . random_int(10, 99) . '-' . random_int(100000, 999999),
            'license_type' => 'non_professional',
            'license_status' => 'valid',
            'verification_status' => $licenseVerification,
            'expiry_date' => now()->addYears(3)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tag = $this->rfidTag($user->user_id, $vehicleId);
        DB::table('user_vehicle_selections')->insertOrIgnore(['user_id' => $user->user_id, 'vehicle_id' => $vehicleId, 'updated_at' => now()]);

        return ['user' => $user, 'vehicle_id' => $vehicleId, 'plate' => $plate, 'tag' => $tag, 'driver_id' => $driverId];
    }

    protected function rfidTag(int $userId, ?int $vehicleId): string
    {
        $uid = 'TEST-RFID-' . Str::upper(Str::random(8));
        $rfidId = DB::table('rfid_tags')->insertGetId(['tag_uid' => $uid, 'tag_type' => 'card', 'tag_status' => 'assigned', 'created_at' => now(), 'updated_at' => now()], 'rfid_id');
        DB::table('rfid_assignments')->insert([
            'rfid_id' => $rfidId, 'user_id' => $userId, 'vehicle_id' => $vehicleId, 'assigned_at' => now(),
            'valid_until' => now()->addYear()->toDateString(), 'assignment_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $uid;
    }

    /** @return array{visitor_id: int, vehicle_id: int, plate: string} */
    protected function visitorVehicle(string $typeCode = 'sedan'): array
    {
        $visitorId = DB::table('visitors')->insertGetId([
            'first_name' => 'Vera', 'last_name' => 'Visitor', 'contact_number' => '09170000000', 'is_blacklisted' => false, 'created_at' => now(), 'updated_at' => now(),
        ], 'visitor_id');
        $plate = $this->nextTestPlate('VIS');
        $vehicleId = DB::table('vehicles')->insertGetId([
            'owner_visitor_id' => $visitorId,
            'vehicle_type_id' => DB::table('vehicle_types')->where('type_code', $typeCode)->value('vehicle_type_id'),
            'plate_number' => $plate,
            'registration_status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vehicle_id');

        return ['visitor_id' => $visitorId, 'vehicle_id' => $vehicleId, 'plate' => $plate];
    }

    /** A pass row written directly (for legacy / edge cases the API never creates). */
    protected function rawPass(array $attributes): string
    {
        $code = (string) Str::uuid();
        DB::table('qr_passes')->insert($attributes + [
            'pass_code' => $code, 'token_hash' => hash('sha256', $code), 'pass_type' => 'visitor',
            'valid_from' => now()->subHour(), 'valid_until' => now()->addDay(), 'max_uses' => 2, 'use_count' => 0,
            'pass_status' => 'active', 'issued_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $code;
    }

    protected function gate(string $direction, array $credential): array
    {
        return $this->postJson("/api/parking/{$direction}", $credential)->assertOk()->json();
    }

    protected function activeTransaction(int $vehicleId): ?object
    {
        return DB::table('parking_transactions')->where('vehicle_id', $vehicleId)->where('transaction_status', 'active')->first();
    }
}
