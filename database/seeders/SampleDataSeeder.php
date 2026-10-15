<?php

namespace Database\Seeders;

use App\Jobs\ProcessLicenseVerification;
use App\Models\DriverLicense;
use App\Models\User;
use App\Models\VehicleAuthorization;
use App\Services\Parking\ParkingAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * SAMPLE TEST DATA for local development: five regular-user accounts with
 * vehicles, vehicle authorizations, RFID tags, driver's license submissions,
 * visitors with QR passes, and yesterday's/today's parking activity in the
 * real Car and Motorcycle Parking Areas (see ParkingAreaSeeder).
 *
 * Parking activity is produced by the real ParkingAccessService (entry
 * checks, slot assignment, exits) and staff slot reports, so it exercises
 * the same code as the application.
 *
 * Every record is identifiable as sample data:
 *   users          user_code "SAMPLE-*" and email "@sample.pass.test"
 *   visitors       email "@sample.pass.test"
 *   vehicles       plate "SMP *" (owned by sample users/visitors); their
 *                  transactions, access events, QR passes and violations
 *   RFID tags      tag_uid "SAMPLE-RFID-*"
 *   slot statuses  maintenance set by "seeder:sample-data" (audit_logs)
 *   license files  storage/app/private/driver-licenses/sample/
 *
 * Safe to run repeatedly: accounts, vehicles, authorizations, RFID tags and
 * licenses are reset in place (stable IDs); activity is recreated. Only
 * records identified above are touched; real records are never modified.
 * Remove everything with RemoveSampleDataSeeder.
 */
class SampleDataSeeder extends Seeder
{
    public const CODE_PREFIX = 'SAMPLE-';

    public const EMAIL_DOMAIN = 'sample.pass.test';

    public const MARK = 'SAMPLE TEST DATA';

    public const ACTOR = 'seeder:sample-data';

    public const RFID_PREFIX = 'SAMPLE-RFID-';

    /** Real slots this seeder puts under maintenance (reverted on removal). */
    public const MAINTENANCE_SLOTS = ['C-50', 'M-80'];

    /** Lot and gate used by earlier versions of this seeder; removed on the next run. */
    private const LEGACY_AREA_CODE = 'SAMPLE-LOT-A';

    private const LEGACY_GATE_CODE = 'SAMPLE-GATE-1';

    public const FILE_DIR = 'driver-licenses/sample';

    /**
     * Fictional people. License numbers use the "Z" prefix, which is not an
     * LTO region code, and every card is printed "SAMPLE - TEST DATA ONLY".
     */
    private const ACCOUNTS = [
        [
            'code' => 'SAMPLE-STU-001', 'first' => 'Andrea', 'last' => 'Villamor', 'user_type' => 'student',
            'dept' => 'COE', 'position' => 'BS Computer Engineering', 'year_level' => '3rd Year', 'sex' => 'female', 'birth' => '2004-03-15',
            'vehicle' => ['plate' => 'SMP 1001', 'type' => 'sedan', 'make' => 'Toyota', 'model' => 'Vios', 'color' => 'White', 'year' => 2021, 'rfid' => true],
            'authorization' => 'approved',
            'license' => ['number' => 'Z01-26-000101', 'type' => 'non_professional', 'years' => 4, 'final' => 'verified'],
        ],
        [
            'code' => 'SAMPLE-STU-002', 'first' => 'Bryan', 'last' => 'Castillo', 'user_type' => 'student',
            'dept' => 'COS', 'position' => 'BS Biology', 'year_level' => '2nd Year', 'sex' => 'male', 'birth' => '2005-07-02',
            'vehicle' => ['plate' => 'SMP 1002', 'type' => 'motorcycle', 'make' => 'Honda', 'model' => 'Click 125i', 'color' => 'Red', 'year' => 2023],
            'authorization' => 'pending',
            'license' => ['number' => 'Z02-26-000202', 'type' => 'student_permit', 'years' => 1, 'final' => 'pending_review'],
        ],
        [
            'code' => 'SAMPLE-FAC-001', 'first' => 'Celia', 'last' => 'Navarro', 'user_type' => 'faculty',
            'dept' => 'COB', 'position' => 'Assistant Professor', 'year_level' => null, 'sex' => 'female', 'birth' => '1986-11-20',
            // Keeps its RFID tag while suspended, so a scan is refused by the authorization check.
            'vehicle' => ['plate' => 'SMP 1003', 'type' => 'suv', 'make' => 'Mitsubishi', 'model' => 'Montero Sport', 'color' => 'Gray', 'year' => 2019, 'rfid' => true],
            'authorization' => 'suspended',
            // The card shows a different number than the one submitted, so the
            // automatic check returns it (Needs Correction).
            'license' => ['number' => 'Z03-26-000303', 'card_number' => 'Z03-26-000330', 'type' => 'professional', 'years' => 5, 'final' => 'needs_correction'],
        ],
        [
            'code' => 'SAMPLE-FAC-002', 'first' => 'Dennis', 'last' => 'Aquino', 'user_type' => 'faculty',
            'dept' => 'COA', 'position' => 'Instructor', 'year_level' => null, 'sex' => 'male', 'birth' => '1990-01-08',
            'vehicle' => ['plate' => 'SMP 1004', 'type' => 'pickup', 'make' => 'Ford', 'model' => 'Ranger', 'color' => 'Blue', 'year' => 2020],
            'authorization' => 'rejected',
            'extra_vehicles' => [
                ['plate' => 'SMP 2004', 'type' => 'motorcycle', 'make' => 'Yamaha', 'model' => 'Mio Gear', 'color' => 'Black', 'year' => 2024, 'rfid' => true, 'authorization' => 'approved'],
            ],
            'license' => ['number' => 'Z04-26-000404', 'type' => 'non_professional', 'years' => 3, 'final' => 'pending_review'],
        ],
        [
            'code' => 'SAMPLE-EMP-001', 'first' => 'Elena', 'last' => 'Bautista', 'user_type' => 'employee',
            'dept' => 'HR', 'position' => 'HR Assistant', 'year_level' => null, 'sex' => 'female', 'birth' => '1993-05-27',
            'vehicle' => ['plate' => 'SMP 1005', 'type' => 'van', 'make' => 'Toyota', 'model' => 'Innova', 'color' => 'Silver', 'year' => 2022, 'rfid' => true],
            'authorization' => 'approved',
            'license' => ['number' => 'Z05-26-000505', 'type' => 'non_professional', 'years' => 10, 'final' => 'verified'],
        ],
    ];

    private const VISITORS = [
        'V1' => ['first' => 'Liza', 'last' => 'Ramos', 'affiliation' => 'Sample Supplier Co. (test)', 'plate' => 'SMP V001', 'make' => 'Isuzu', 'model' => 'Traviz', 'type' => 'pickup'],
        'V2' => ['first' => 'Marco', 'last' => 'Dizon', 'affiliation' => 'Parent visitor (test)', 'plate' => 'SMP V002', 'make' => 'Nissan', 'model' => 'Almera', 'type' => 'sedan'],
        'V3' => ['first' => 'Nina', 'last' => 'Ocampo', 'affiliation' => 'Guest lecturer (test)', 'plate' => 'SMP V003', 'make' => 'Hyundai', 'model' => 'Accent', 'type' => 'sedan'],
        'V4' => ['first' => 'Oscar', 'last' => 'Velasco', 'affiliation' => 'Delivery rider (test)', 'plate' => 'SMP V004', 'make' => 'Yamaha', 'model' => 'NMAX', 'type' => 'motorcycle'],
    ];

    /** @var string[] */
    private array $warnings = [];

    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Sample data can only be seeded in local/testing environments.');
        }

        if (!extension_loaded('gd')) {
            throw new RuntimeException('The GD extension is required to generate the sample license documents.');
        }

        $password = env('PASS_SAMPLE_PASSWORD');

        if (!is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set PASS_SAMPLE_PASSWORD (at least 12 characters) in your local .env.');
        }

        $this->guardAgainstConflicts();

        // The real parking areas, slots and violation types (idempotent configuration).
        $this->call([ParkingAreaSeeder::class, ViolationTypeSeeder::class]);

        $staff = $this->staffReviewer();

        // Clear previous sample activity and history. Accounts, vehicles,
        // authorizations and licenses are kept and reset in place below.
        self::removeSampleRecords(keepUsers: true);

        $users = DB::transaction(function () use ($password, $staff) {
            $users = [];

            foreach (self::ACCOUNTS as $account) {
                $users[$account['code']] = $this->upsertAccount($account, $password);
            }

            foreach (self::ACCOUNTS as $account) {
                foreach ($this->vehiclesOf($account) as $vehicle) {
                    $this->createVehicle($account, $users[$account['code']], $vehicle, $staff);
                }
            }

            return $users;
        });

        $this->setMaintenanceSlots();
        $this->createParkingActivity($users, $staff);

        // License checks run the real OCR pipeline, outside the transaction.
        foreach (self::ACCOUNTS as $account) {
            $this->createLicense($account, $users[$account['code']], $staff);
        }

        $this->report($users);
    }

    // ------------------------------------------------------------------ accounts

    private function guardAgainstConflicts(): void
    {
        foreach (self::ACCOUNTS as $account) {
            $email = $this->email($account['code']);
            $byEmail = DB::table('users')->where('email', $email)->value('user_code');
            $byCode = DB::table('users')->where('user_code', $account['code'])->value('email');

            if (($byEmail && $byEmail !== $account['code']) || ($byCode && $byCode !== $email)) {
                throw new RuntimeException("{$account['code']} or {$email} belongs to a non-sample account. Nothing was changed.");
            }
        }

        foreach (self::VISITORS as $visitor) {
            $owner = DB::table('vehicles')->where('plate_number', $visitor['plate'])->first();
            if ($owner && !$this->isSampleVehicle($owner)) {
                throw new RuntimeException("Plate {$visitor['plate']} belongs to a non-sample vehicle. Nothing was changed.");
            }
        }

        foreach (self::ACCOUNTS as $account) {
            foreach ($this->vehiclesOf($account) as $vehicle) {
                $owner = DB::table('vehicles')->where('plate_number', $vehicle['plate'])->first();
                if ($owner && !$this->isSampleVehicle($owner)) {
                    throw new RuntimeException("Plate {$vehicle['plate']} belongs to a non-sample vehicle. Nothing was changed.");
                }
            }
        }

        $tags = DB::table('rfid_tags as t')
            ->leftJoin('rfid_assignments as a', fn ($j) => $j->on('a.rfid_id', '=', 't.rfid_id')->where('a.assignment_status', 'active'))
            ->where('t.tag_uid', 'like', self::RFID_PREFIX . '%')
            ->whereNotNull('a.user_id')
            ->whereNotIn('a.user_id', self::sampleUserIds())
            ->exists();

        if ($tags) {
            throw new RuntimeException('A ' . self::RFID_PREFIX . '* tag is assigned to a non-sample user. Nothing was changed.');
        }
    }

    /**
     * The account's vehicles, each with its authorization status.
     */
    private function vehiclesOf(array $account): array
    {
        return array_merge(
            [$account['vehicle'] + ['authorization' => $account['authorization']]],
            $account['extra_vehicles'] ?? []
        );
    }

    private function isSampleVehicle(object $vehicle): bool
    {
        if ($vehicle->owner_user_id) {
            return in_array($vehicle->owner_user_id, self::sampleUserIds()->all());
        }

        return in_array($vehicle->owner_visitor_id, self::sampleVisitorIds()->all());
    }

    private function email(string $code): string
    {
        return strtolower($code) . '@' . self::EMAIL_DOMAIN;
    }

    private function staffReviewer(): ?User
    {
        $staff = User::where('user_code', 'SEC-DEMO-001')->first()
            ?? User::where('account_status', 'active')->whereIn('user_type', ['security', 'admin'])
                ->where('user_code', 'not like', self::CODE_PREFIX . '%')->first();

        if (!$staff) {
            $this->warnings[] = 'No parking staff account found; reviewer fields were left empty. Run ParkingStaffDemoSeeder to add one.';
        }

        return $staff;
    }

    private function upsertAccount(array $a, string $password): User
    {
        $departmentId = DB::table('departments')->where('department_code', $a['dept'])->value('department_id');
        $roleId = DB::table('roles')->where('role_code', $a['user_type'])->where('is_active', true)->value('role_id');

        if (!$roleId) {
            throw new RuntimeException("Role '{$a['user_type']}' not found. Run RoleSeeder first.");
        }

        $user = User::withTrashed()->firstOrNew(['user_code' => $a['code']]);
        $user->forceFill([
            'email' => $this->email($a['code']),
            'password' => Hash::make($password),
            'user_type' => $a['user_type'],
            'account_status' => 'active',
            'email_verified_at' => now(),
            'deleted_at' => null,
        ])->save();

        DB::table('user_profiles')->updateOrInsert(
            ['user_id' => $user->user_id],
            [
                'department_id' => $departmentId,
                'first_name' => $a['first'],
                'last_name' => $a['last'],
                'sex' => $a['sex'],
                'birth_date' => $a['birth'],
                'contact_number' => '0900-000-' . substr($a['code'], -4),
                'address_line' => self::MARK,
                'program_or_position' => $a['position'],
                'year_level' => $a['year_level'],
                'emergency_contact_name' => self::MARK,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Exactly one role, matching the account type.
        DB::table('user_roles')->where('user_id', $user->user_id)->delete();
        DB::table('user_roles')->insert(['user_id' => $user->user_id, 'role_id' => $roleId, 'assigned_at' => now(), 'assigned_by' => null]);

        return $user;
    }

    // ------------------------------------------------------------------ vehicles

    private function vehicleTypeId(string $code): int
    {
        $id = DB::table('vehicle_types')->where('type_code', $code)->value('vehicle_type_id');

        if (!$id) {
            throw new RuntimeException("Vehicle type '{$code}' not found. Run VehicleTypeSeeder first.");
        }

        return $id;
    }

    private function createVehicle(array $a, User $user, array $v, ?User $staff): void
    {
        $today = CarbonImmutable::now()->startOfDay();
        $suffix = str_replace(' ', '', substr($v['plate'], 4));

        // Existing sample rows are reset in place so their IDs stay stable
        // across reruns; pages left open keep pointing at valid records.
        $vehicleId = $this->upsert('vehicles', 'vehicle_id', ['plate_number' => $v['plate']], [
            'owner_user_id' => $user->user_id,
            'owner_visitor_id' => null,
            'vehicle_type_id' => $this->vehicleTypeId($v['type']),
            'make' => $v['make'],
            'model' => $v['model'],
            'color' => $v['color'],
            'year_model' => $v['year'],
            'or_number' => 'SAMPLE-OR-' . $suffix,
            'cr_number' => 'SAMPLE-CR-' . $suffix,
            'chassis_number' => null,
            'registration_status' => 'registered',
            'deleted_at' => null,
            'created_at' => $today->subDays(45),
            'updated_at' => $today->subDays(45),
        ]);

        $driverId = $this->upsert('drivers', 'driver_id', ['user_id' => $user->user_id], [
            'first_name' => $a['first'],
            'middle_name' => null,
            'last_name' => $a['last'],
            'contact_number' => '0900-000-' . substr($a['code'], -4),
            'driver_status' => 'active',
            'created_at' => $today->subDays(45),
            'updated_at' => $today->subDays(45),
        ]);

        DB::table('vehicle_driver')->where('vehicle_id', $vehicleId)->delete();
        DB::table('vehicle_driver')->insert([
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
            'relationship' => 'owner',
            'is_primary' => true,
            'created_at' => $today->subDays(45),
            'updated_at' => $today->subDays(45),
        ]);

        $status = $v['authorization'];
        $reviewed = $status !== 'pending';
        $hasValidity = in_array($status, ['approved', 'suspended'], true);

        // Keep the vehicle's first authorization (stable ID) and drop any extras.
        $authorizationId = DB::table('vehicle_authorizations')->where('vehicle_id', $vehicleId)->min('authorization_id');
        DB::table('vehicle_authorizations')->where('vehicle_id', $vehicleId)
            ->when($authorizationId, fn ($q) => $q->where('authorization_id', '!=', $authorizationId))
            ->delete();

        $this->upsert('vehicle_authorizations', 'authorization_id', $authorizationId ? ['authorization_id' => $authorizationId] : [], [
            'vehicle_id' => $vehicleId,
            // Null = valid for the parking area matching the vehicle type.
            'parking_area_id' => null,
            'sticker_number' => $hasValidity ? 'SAMPLE-STK-' . $suffix : null,
            'authorization_status' => $status,
            'valid_from' => $hasValidity ? $today->subDays(40)->toDateString() : null,
            'valid_until' => $hasValidity ? $today->addMonths(10)->toDateString() : null,
            'requested_at' => $today->subDays($status === 'pending' ? 2 : 44)->setTime(9, 0),
            'reviewed_by' => $reviewed ? $staff?->user_id : null,
            'reviewed_at' => $reviewed ? $today->subDays($status === 'suspended' ? 3 : 41)->setTime(14, 0) : null,
            'remarks' => match ($status) {
                'suspended' => 'SAMPLE: Suspended pending review of a parking violation (test data).',
                'rejected' => 'SAMPLE: OR/CR details did not match the vehicle (test data).',
                default => null,
            },
            'created_at' => $today->subDays(44),
            'updated_at' => $today->subDays($status === 'suspended' ? 3 : 41),
        ]);

        if (!empty($v['rfid'])) {
            $this->assignRfidTag($user, $vehicleId, self::RFID_PREFIX . $suffix, $staff);
        }
    }

    /**
     * A sample RFID tag with one active assignment to this owner and vehicle.
     */
    private function assignRfidTag(User $user, int $vehicleId, string $tagUid, ?User $staff): void
    {
        $since = CarbonImmutable::now()->startOfDay()->subDays(40);

        $rfidId = $this->upsert('rfid_tags', 'rfid_id', ['tag_uid' => $tagUid], [
            'tag_type' => 'sticker',
            'serial_number' => $tagUid,
            'batch_code' => self::MARK,
            'tag_status' => 'assigned',
            'acquired_at' => $since->toDateString(),
            'created_at' => $since,
            'updated_at' => $since,
        ]);

        $assignmentId = DB::table('rfid_assignments')->where('rfid_id', $rfidId)->min('assignment_id');
        DB::table('rfid_assignments')->where('rfid_id', $rfidId)
            ->when($assignmentId, fn ($q) => $q->where('assignment_id', '!=', $assignmentId))
            ->delete();

        $this->upsert('rfid_assignments', 'assignment_id', $assignmentId ? ['assignment_id' => $assignmentId] : [], [
            'rfid_id' => $rfidId,
            'user_id' => $user->user_id,
            'vehicle_id' => $vehicleId,
            'assigned_by' => $staff?->user_id,
            'assigned_at' => $since,
            'valid_until' => $since->addYear()->toDateString(),
            'unassigned_at' => null,
            'assignment_status' => 'active',
            'remarks' => self::MARK,
            'created_at' => $since,
            'updated_at' => $since,
        ]);
    }

    /**
     * Put two real slots under maintenance, marked as done by this seeder so
     * removal can return exactly these to service.
     */
    private function setMaintenanceSlots(): void
    {
        foreach (self::MAINTENANCE_SLOTS as $code) {
            $slot = DB::table('parking_slots')->where('slot_code', $code)->first();

            if (!$slot || $slot->slot_status !== 'available' ||
                DB::table('parking_transactions')->where('slot_id', $slot->slot_id)->where('transaction_status', 'active')->exists()) {
                continue;
            }

            DB::table('parking_slots')->where('slot_id', $slot->slot_id)->update(['slot_status' => 'maintenance', 'updated_at' => now()]);
            DB::table('audit_logs')->insert([
                'actor_label' => self::ACTOR,
                'action' => 'parking_slot_maintenance',
                'auditable_type' => 'parking_slot',
                'auditable_id' => $slot->slot_id,
                'old_values' => json_encode(['slot_status' => 'available']),
                'new_values' => json_encode(['slot_status' => 'maintenance', 'reason' => 'SAMPLE: line repainting (test data)']),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Update the row matching $match (an empty match always inserts) and return its ID.
     */
    private function upsert(string $table, string $key, array $match, array $values): int
    {
        $id = $match ? DB::table($table)->where($match)->value($key) : null;

        if ($id) {
            DB::table($table)->where($key, $id)->update($values);
            return (int) $id;
        }

        return (int) DB::table($table)->insertGetId($match + $values, $key);
    }

    // ---------------------------------------------------------- parking activity

    /**
     * Yesterday's and today's activity, produced by the real ParkingAccessService
     * (entry checks, slot assignment, exits) with timestamps spread across the
     * part of today (application timezone) that has already passed.
     *
     * Today: 6 entries, 3 exits, 4 denied, 2 visitors still parked.
     * Now parked: 4 cars (2 of them visitors) and 1 motorcycle.
     */
    private function createParkingActivity(array $users, ?User $staff): void
    {
        $now = CarbonImmutable::now();
        $start = $now->startOfDay();
        $elapsed = max(1, $start->diffInSeconds($now));
        $at = fn (float $fraction) => $start->addSeconds((int) floor($elapsed * $fraction));
        $yesterday = $start->subDay();

        // Visitors, their vehicles, and QR passes (V4's pass has expired).
        $passes = [];
        foreach (self::VISITORS as $key => $v) {
            $visitorId = DB::table('visitors')->insertGetId([
                'first_name' => $v['first'],
                'last_name' => $v['last'],
                'contact_number' => '0900-111-000' . substr($key, 1),
                'email' => strtolower("visitor.{$v['first']}.{$v['last']}") . '@' . self::EMAIL_DOMAIN,
                'id_type' => 'other',
                'id_number' => 'SAMPLE-ID-' . $key,
                'affiliation' => $v['affiliation'],
                'is_blacklisted' => false,
                'created_at' => $yesterday,
                'updated_at' => $yesterday,
            ], 'visitor_id');

            $vehicleId = DB::table('vehicles')->insertGetId([
                'owner_visitor_id' => $visitorId,
                'vehicle_type_id' => $this->vehicleTypeId($v['type']),
                'plate_number' => $v['plate'],
                'make' => $v['make'],
                'model' => $v['model'],
                'registration_status' => 'registered',
                'created_at' => $yesterday,
                'updated_at' => $yesterday,
            ], 'vehicle_id');

            $expired = $key === 'V4';
            $passes[$key] = (string) Str::uuid();
            DB::table('qr_passes')->insert([
                'pass_code' => $passes[$key],
                'token_hash' => hash('sha256', Str::random(40)),
                'pass_type' => 'visitor',
                'visitor_id' => $visitorId,
                'vehicle_id' => $vehicleId,
                'valid_from' => $expired ? $yesterday->subDay() : $yesterday,
                'valid_until' => $expired ? $yesterday : $start->endOfDay(),
                'max_uses' => 2,
                'use_count' => 0,
                'pass_status' => $expired ? 'expired' : 'active',
                'issued_by' => $staff?->user_id,
                'issued_at' => $yesterday,
                'created_at' => $yesterday,
                'updated_at' => $yesterday,
            ]);
        }

        $access = app(ParkingAccessService::class);
        $rfid = fn (string $plate) => ['rfid_tag_uid' => self::RFID_PREFIX . str_replace(' ', '', substr($plate, 4))];
        $qr = fn (string $key) => ['qr_pass_code' => $passes[$key]];
        $plate = fn (string $plate) => ['plate_number' => $plate];
        $in = function (array $credential, $time, string $expect) use ($access, $staff) {
            $result = $access->enter($credential, $staff, ['at' => $time]);
            if ($result['status'] !== $expect) {
                $this->warnings[] = 'Entry ' . json_encode($credential) . " returned {$result['status']} (expected {$expect}): {$result['message']}";
            }
            return $result;
        };
        $out = function (array $credential, $time) use ($access, $staff) {
            $result = $access->exit($credential, $staff, ['at' => $time]);
            if ($result['status'] !== 'completed') {
                $this->warnings[] = 'Exit ' . json_encode($credential) . " returned {$result['status']}: {$result['message']}";
            }
        };

        // Yesterday (not counted in today's statistics).
        $in($rfid('SMP 1005'), $yesterday->setTime(7, 30), 'granted');   // Elena, exits today
        $in($rfid('SMP 1001'), $yesterday->setTime(7, 45), 'granted');   // Andrea
        $in($rfid('SMP 2004'), $yesterday->setTime(8, 10), 'granted');   // Dennis, motorcycle
        $in($qr('V3'), $yesterday->setTime(15, 0), 'granted');           // Nina, still parked
        $out($rfid('SMP 2004'), $yesterday->setTime(16, 40));
        $out($rfid('SMP 1001'), $yesterday->setTime(17, 10));

        // Today.
        $out($rfid('SMP 1005'), $at(0.05));
        $in($rfid('SMP 1001'), $at(0.10), 'granted');
        $in($qr('V2'), $at(0.20), 'granted');
        $in($plate('SMP 1002'), $at(0.30), 'denied');                     // authorization pending
        $out($rfid('SMP 1001'), $at(0.35));
        $in($rfid('SMP 1003'), $at(0.40), 'denied');                      // authorization suspended
        $in($rfid('SMP 1005'), $at(0.45), 'granted');
        $out($qr('V2'), $at(0.50));
        $in($qr('V1'), $at(0.55), 'granted');
        $in($rfid('SMP 1001'), $at(0.60), 'granted');
        $andreaSlot = $in($rfid('SMP 1001'), $at(0.61), 'already_inside'); // repeated scan: no new record
        $in($rfid('SMP 2004'), $at(0.62), 'granted');
        $in($plate('SMP 1004'), $at(0.65), 'denied');                     // authorization rejected
        $in($qr('V4'), $at(0.70), 'denied');                              // QR pass expired

        DB::table('parking_transactions')->whereIn('vehicle_id', self::sampleVehicleIds())->update(['remarks' => self::MARK]);

        if (!$staff) {
            $this->warnings[] = 'No parking staff account: slot confirmations and wrong-slot incidents were not created.';
            return;
        }

        // Staff slot observations (there is no slot-level sensor).
        $active = fn (string $plate) => DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
            ->where('v.plate_number', $plate)->where('t.transaction_status', 'active')->first(['t.transaction_id', 't.slot_id']);
        $slotId = fn (string $code) => (int) DB::table('parking_slots')->where('slot_code', $code)->value('slot_id');

        foreach (['SMP 1005', 'SMP V001'] as $confirmed) {
            $txn = $active($confirmed);
            $access->reportObservedSlot($txn->transaction_id, $txn->slot_id, $staff);
        }

        // Open incident: Andrea's car found in a different car slot.
        $wrong = $andreaSlot['slot_code'] === 'C-20' ? 'C-21' : 'C-20';
        $access->reportObservedSlot($active('SMP 1001')->transaction_id, $slotId($wrong), $staff);

        // Resolved incident: Dennis's motorcycle found in the car area, then moved back.
        $moto = $active('SMP 2004');
        $report = $access->reportObservedSlot($moto->transaction_id, $slotId('C-30'), $staff);
        if (!empty($report['violation_id'])) {
            DB::table('parking_violations')->where('violation_id', $report['violation_id'])->update([
                'violation_status' => 'resolved',
                'resolved_by' => $staff->user_id,
                'resolved_at' => now(),
                'resolution_remarks' => 'Vehicle moved back to its assigned slot (confirmed). SAMPLE',
            ]);
            DB::table('parking_transactions')->where('transaction_id', $moto->transaction_id)
                ->update(['slot_confirmed_at' => now(), 'slot_confirmed_by' => $staff->user_id]);
        }
    }


    // ------------------------------------------------------------------ licenses

    private function createLicense(array $a, User $user, ?User $staff): void
    {
        $l = $a['license'];
        $expiry = CarbonImmutable::parse($a['birth'])->setYear((int) now()->format('Y') + $l['years']);
        $driverId = DB::table('drivers')->where('user_id', $user->user_id)->value('driver_id');

        $path = self::FILE_DIR . '/' . strtolower($a['code']) . '.png';
        Storage::disk('private')->put($path, $this->sampleCard([
            'name' => strtoupper("{$a['last']}, {$a['first']}"),
            'number' => $l['card_number'] ?? $l['number'],
            'expiry' => $expiry->format('Y/m/d'),
            'birth' => CarbonImmutable::parse($a['birth'])->format('Y/m/d'),
            'type' => strtoupper(str_replace('_', '-', $l['type'])),
        ]));

        $runId = (string) Str::uuid();
        $submittedAt = now()->subDays(match ($l['final']) { 'verified' => 20, 'needs_correction' => 3, default => 1 });

        // Reset the driver's existing submission in place (stable ID); remove extras
        // and any file left by a manual resubmission during testing.
        $existing = DriverLicense::where('driver_id', $driverId)->orderBy('license_id')->get();
        $license = $existing->shift() ?? new DriverLicense();
        foreach ($existing as $extra) {
            if ($extra->license_image_path) {
                Storage::disk('private')->delete($extra->license_image_path);
            }
            $extra->delete();
        }
        if ($license->license_image_path && $license->license_image_path !== $path) {
            Storage::disk('private')->delete($license->license_image_path);
        }

        $license->forceFill([
            'driver_id' => $driverId,
            'restriction_codes' => null,
            'issue_date' => null,
            'ocr_result' => null,
            'verification_reason' => null,
            'checked_at' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'license_number' => $l['number'],
            'license_type' => $l['type'],
            'expiry_date' => $expiry->toDateString(),
            'issuing_agency' => self::MARK,
            'license_image_path' => $path,
            'original_file_name' => strtolower($a['code']) . '-sample-license.png',
            'license_status' => 'valid',
            'verification_status' => 'processing',
            'verification_source' => 'automatic',
            'verification_run_id' => $runId,
            'submitted_at' => $submittedAt,
        ])->save();

        // The real automatic check (OCR + validation rules).
        ProcessLicenseVerification::dispatchSync($license->license_id, $runId);
        $license->refresh();
        $automatic = $license->verification_status;

        $expectedAutomatic = $l['final'] === 'needs_correction' ? 'needs_correction' : 'pending_review';

        if ($automatic !== $expectedAutomatic) {
            $this->warnings[] = "{$a['code']}: automatic check returned '{$automatic}' (expected '{$expectedAutomatic}'). "
                . 'Is Tesseract installed and TESSERACT_PATH set? A recorded staff decision was applied instead.';
        }

        // Staff decisions on top of the automatic result. Verification here is
        // a PASS staff decision on sample data, not an official LTO verification.
        if ($l['final'] === 'verified' || $automatic !== $expectedAutomatic) {
            $this->recordStaffDecision($license, $l['final'], $staff, $submittedAt);
        }
    }

    private function recordStaffDecision(DriverLicense $license, string $decision, ?User $staff, $submittedAt): void
    {
        $old = $license->only(['verification_status', 'verification_source', 'verification_reason', 'reviewed_by']);

        $license->forceFill([
            'verification_status' => $decision,
            'verification_source' => 'staff',
            'verification_reason' => $decision === 'needs_correction'
                ? 'The license number on the document does not match the number you entered. Check the number or upload the correct license. (sample)'
                : null,
            'reviewed_by' => $staff?->user_id,
            'reviewed_at' => CarbonImmutable::parse($submittedAt)->addDay(),
        ])->save();

        DB::table('audit_logs')->insert([
            'user_id' => null,
            'actor_label' => 'seeder:sample-data' . ($staff ? " (as {$staff->user_code})" : ''),
            'action' => "license_review_{$decision}",
            'auditable_type' => DriverLicense::class,
            'auditable_id' => $license->license_id,
            'old_values' => json_encode($old),
            'new_values' => json_encode($license->only(['verification_status', 'verification_source', 'verification_reason', 'reviewed_by'])),
            'created_at' => now(),
        ]);
    }

    /**
     * A plainly fake test card. It carries no government name, seal, photo or
     * security features, and is watermarked SAMPLE / TEST DATA ONLY.
     */
    private function sampleCard(array $o): string
    {
        $w = 1600;
        $h = 1000;
        $im = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($im, 255, 255, 255);
        $ink = imagecolorallocate($im, 30, 30, 30);
        $grey = imagecolorallocate($im, 110, 110, 110);
        $red = imagecolorallocate($im, 200, 30, 30);
        $watermark = imagecolorallocatealpha($im, 230, 120, 120, 100);
        imagefilledrectangle($im, 0, 0, $w, $h, $white);
        imagesetthickness($im, 12);
        imagerectangle($im, 6, 6, $w - 7, $h - 7, $red);

        $font = $this->cardFont();
        $text = function (float $size, int $x, int $y, string $s, int $color, float $angle = 0) use ($im, $font) {
            if ($font) {
                imagettftext($im, $size, $angle, $x, $y, $color, $font, $s);
                return;
            }
            // Built-in bitmap font, scaled up, when no TrueType font is available.
            $small = imagecreatetruecolor(strlen($s) * 9 + 2, 16);
            imagefilledrectangle($small, 0, 0, imagesx($small), 16, imagecolorallocate($small, 255, 255, 255));
            imagestring($small, 5, 1, 0, $s, imagecolorallocate($small, 30, 30, 30));
            $scale = $size / 9;
            imagecopyresampled($im, $small, $x, (int) ($y - 14 * $scale), 0, 0, (int) (imagesx($small) * $scale), (int) (16 * $scale), imagesx($small), 16);
            imagedestroy($small);
        };

        if ($font) {
            $text(150, 330, 880, 'SAMPLE', $watermark, 22);
        }

        imagefilledrectangle($im, 12, 12, $w - 13, 150, $red);
        $headerColor = $font ? $white : $ink;
        if (!$font) {
            imagefilledrectangle($im, 12, 12, $w - 13, 150, $white);
        }
        $text(40, 60, 85, 'SAMPLE DRIVER LICENSE - TEST DATA ONLY', $headerColor);
        $text(20, 60, 130, 'NOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.', $headerColor);

        $rows = [
            ['Last Name, First Name', $o['name']],
            ['License No.', $o['number']],
            ['Expiration Date', $o['expiry']],
            ['Date of Birth', $o['birth']],
            ['License Type', $o['type']],
        ];

        foreach ($rows as $i => [$label, $value]) {
            $y = 250 + $i * 135;
            $text(20, 80, $y, $label, $grey);
            $text(36, 80, $y + 55, $value, $ink);
        }

        $text(22, 80, 960, 'SAMPLE  *  TEST DATA ONLY  *  NOT VALID FOR ANY PURPOSE', $red);

        ob_start();
        imagepng($im);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    private function cardFont(): ?string
    {
        foreach ([
            env('SAMPLE_CARD_FONT'),
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/Library/Fonts/Arial.ttf',
        ] as $path) {
            if ($path && is_file($path)) {
                return $path;
            }
        }

        $this->warnings[] = 'No TrueType font found; sample cards use the built-in bitmap font, which OCR reads less reliably. Set SAMPLE_CARD_FONT to a .ttf file.';

        return null;
    }

    // ------------------------------------------------------------------ removal

    public static function sampleUserIds()
    {
        return DB::table('users')
            ->where('user_code', 'like', self::CODE_PREFIX . '%')
            ->where('email', 'like', '%@' . self::EMAIL_DOMAIN)
            ->pluck('user_id');
    }

    public static function sampleVisitorIds()
    {
        return DB::table('visitors')->where('email', 'like', '%@' . self::EMAIL_DOMAIN)->pluck('visitor_id');
    }

    public static function sampleVehicleIds()
    {
        return DB::table('vehicles')
            ->where(fn ($q) => $q->whereIn('owner_user_id', self::sampleUserIds())->orWhereIn('owner_visitor_id', self::sampleVisitorIds()))
            ->pluck('vehicle_id');
    }

    /**
     * Delete sample records only, in foreign-key order.
     *
     * With $keepUsers (a reset before reseeding) the accounts, their vehicles,
     * vehicle authorizations, drivers, licenses and RFID tags are kept and
     * later reset in place, so their IDs never change. Only the activity
     * (transactions, access events, QR passes, visitors, violations), review
     * history and related notifications are cleared. Without it, everything
     * sample is removed and seeder-set maintenance slots return to service.
     */
    public static function removeSampleRecords(bool $keepUsers): array
    {
        $userIds = self::sampleUserIds();
        $visitorIds = self::sampleVisitorIds();
        $visitorVehicleIds = DB::table('vehicles')->whereIn('owner_visitor_id', $visitorIds)->pluck('vehicle_id');
        $vehicleIds = self::sampleVehicleIds();
        $driverIds = DB::table('drivers')->whereIn('user_id', $userIds)->pluck('driver_id');
        $licenses = DB::table('driver_licenses')->whereIn('driver_id', $driverIds)->get(['license_id', 'license_image_path']);
        $authorizationIds = DB::table('vehicle_authorizations')->whereIn('vehicle_id', $vehicleIds)->pluck('authorization_id');
        $legacyAreaIds = DB::table('parking_areas')->where('area_code', self::LEGACY_AREA_CODE)->pluck('parking_area_id');
        $legacyGateIds = DB::table('access_gates')->where('gate_code', self::LEGACY_GATE_CODE)->pluck('gate_id');
        $tagIds = DB::table('rfid_tags')->where('tag_uid', 'like', self::RFID_PREFIX . '%')->pluck('rfid_id');

        $transactionIds = DB::table('parking_transactions')
            ->where(fn ($q) => $q->whereIn('vehicle_id', $vehicleIds)->orWhereIn('user_id', $userIds)
                ->orWhereIn('visitor_id', $visitorIds)->orWhereIn('parking_area_id', $legacyAreaIds))
            ->pluck('transaction_id');
        $eventIds = DB::table('access_events')
            ->where(fn ($q) => $q->whereIn('vehicle_id', $vehicleIds)->orWhereIn('user_id', $userIds)
                ->orWhereIn('visitor_id', $visitorIds)->orWhereIn('gate_id', $legacyGateIds)->orWhereIn('rfid_id', $tagIds))
            ->pluck('event_id');
        $violationIds = DB::table('parking_violations')
            ->where(fn ($q) => $q->whereIn('vehicle_id', $vehicleIds)->orWhereIn('parking_transaction_id', $transactionIds))
            ->pluck('violation_id');
        $maintenanceAudit = DB::table('audit_logs')->where('actor_label', self::ACTOR)->where('auditable_type', 'parking_slot');

        $counts = [];

        DB::transaction(function () use ($userIds, $visitorIds, $visitorVehicleIds, $vehicleIds, $driverIds, $licenses, $authorizationIds, $legacyAreaIds, $legacyGateIds, $tagIds, $transactionIds, $eventIds, $violationIds, $maintenanceAudit, $keepUsers, &$counts) {
            // Sample users' own notifications, and staff alerts about sample activity.
            $counts['notifications'] = DB::table('notifications')
                ->where(fn ($q) => $q->whereIn('user_id', $userIds)
                    ->orWhere(fn ($q) => $q->where('related_type', 'parking_violation')->whereIn('related_id', $violationIds))
                    ->orWhere(fn ($q) => $q->where('related_type', 'access_event')->whereIn('related_id', $eventIds))
                    ->orWhere(fn ($q) => $q->where('related_type', 'parking_transaction')->whereIn('related_id', $transactionIds)))
                ->delete();

            $counts['parking_violations'] = DB::table('parking_violations')->whereIn('violation_id', $violationIds)->delete();
            $counts['parking_transactions'] = DB::table('parking_transactions')->whereIn('transaction_id', $transactionIds)->delete();
            $counts['access_events'] = DB::table('access_events')->whereIn('event_id', $eventIds)->delete();
            $counts['qr_passes'] = DB::table('qr_passes')
                ->where(fn ($q) => $q->whereIn('visitor_id', $visitorIds)->orWhereIn('vehicle_id', $vehicleIds))
                ->delete();

            $counts['audit_logs'] = DB::table('audit_logs')
                ->where(fn ($q) => $q
                    ->where(fn ($q) => $q->where('auditable_type', VehicleAuthorization::class)->whereIn('auditable_id', $authorizationIds))
                    ->orWhere(fn ($q) => $q->where('auditable_type', DriverLicense::class)->whereIn('auditable_id', $licenses->pluck('license_id')))
                    ->orWhere(fn ($q) => $q->where('auditable_type', 'parking_violation')->whereIn('auditable_id', $violationIds)))
                ->delete();
            $counts['vehicles'] = DB::table('vehicles')->whereIn('vehicle_id', $visitorVehicleIds)->delete();
            $counts['visitors'] = DB::table('visitors')->whereIn('visitor_id', $visitorIds)->delete();

            // The lot and gate from earlier versions of this seeder.
            DB::table('vehicle_authorizations')->whereIn('parking_area_id', $legacyAreaIds)->update(['parking_area_id' => null]);
            $counts['access_gates'] = DB::table('access_gates')->whereIn('gate_id', $legacyGateIds)->delete();
            $counts['parking_areas'] = DB::table('parking_areas')->whereIn('parking_area_id', $legacyAreaIds)->delete();

            if (!$keepUsers) {
                // Return only the slots this seeder put under maintenance.
                $slotIds = (clone $maintenanceAudit)->pluck('auditable_id');
                $counts['parking_slots_returned_to_service'] = DB::table('parking_slots')
                    ->whereIn('slot_id', $slotIds)->where('slot_status', 'maintenance')
                    ->update(['slot_status' => 'available', 'updated_at' => now()]);
                (clone $maintenanceAudit)->delete();

                $counts['rfid_assignments'] = DB::table('rfid_assignments')->whereIn('rfid_id', $tagIds)->delete();
                $counts['rfid_tags'] = DB::table('rfid_tags')->whereIn('rfid_id', $tagIds)->delete();
                $counts['vehicle_authorizations'] = DB::table('vehicle_authorizations')->whereIn('authorization_id', $authorizationIds)->delete();
                $counts['vehicle_driver'] = DB::table('vehicle_driver')
                    ->where(fn ($q) => $q->whereIn('vehicle_id', $vehicleIds)->orWhereIn('driver_id', $driverIds))
                    ->delete();
                $counts['driver_licenses'] = DB::table('driver_licenses')->whereIn('license_id', $licenses->pluck('license_id'))->delete();
                $counts['drivers'] = DB::table('drivers')->whereIn('driver_id', $driverIds)->delete();
                $counts['vehicles'] += DB::table('vehicles')->whereIn('vehicle_id', $vehicleIds)->delete();
                $counts['personal_access_tokens'] = DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)->whereIn('tokenable_id', $userIds)->delete();
                $counts['login_attempts'] = DB::table('login_attempts')->whereIn('user_id', $userIds)->delete();
                $counts['user_roles'] = DB::table('user_roles')->whereIn('user_id', $userIds)->delete();
                $counts['user_profiles'] = DB::table('user_profiles')->whereIn('user_id', $userIds)->delete();
                $counts['users'] = DB::table('users')->whereIn('user_id', $userIds)->delete();
            }
        });

        // Files are removed after the rows, so a failed transaction keeps them.
        // On a reset the license rows survive and the seeder replaces their files.
        if (!$keepUsers) {
            foreach ($licenses as $license) {
                if ($license->license_image_path) {
                    Storage::disk('private')->delete($license->license_image_path);
                }
            }
            Storage::disk('private')->deleteDirectory(self::FILE_DIR);
        }

        return array_filter($counts);
    }

    // ------------------------------------------------------------------ output

    private function report(array $users): void
    {
        $this->command->info('Sample test data created (' . self::MARK . ').');
        $this->command->line('Password for all five accounts: the value of PASS_SAMPLE_PASSWORD in your .env');

        $rows = [];
        foreach (self::ACCOUNTS as $a) {
            $user = $users[$a['code']];
            $license = DriverLicense::join('drivers', 'drivers.driver_id', '=', 'driver_licenses.driver_id')
                ->where('drivers.user_id', $user->user_id)->first(['driver_licenses.*']);
            foreach ($this->vehiclesOf($a) as $i => $v) {
                $slot = DB::table('parking_transactions as t')->join('vehicles as veh', 'veh.vehicle_id', '=', 't.vehicle_id')
                    ->join('parking_slots as s', 's.slot_id', '=', 't.slot_id')
                    ->where('veh.plate_number', $v['plate'])->where('t.transaction_status', 'active')->value('s.slot_code');
                $rows[] = [
                    $i ? '' : $a['code'],
                    $i ? '' : "{$a['first']} {$a['last']}",
                    $i ? '' : $a['user_type'],
                    "{$v['plate']} ({$v['type']})",
                    $v['authorization'],
                    $slot ?? '—',
                    $i ? '' : $license?->verification_status . ($license?->verification_source === 'staff' ? ' (staff)' : ' (auto)'),
                ];
            }
        }

        $this->command->table(['User ID', 'Name', 'Role', 'Vehicle', 'Vehicle auth', 'Parked in', 'License'], $rows);
        $this->command->line('Login email: <user id in lowercase>@' . self::EMAIL_DOMAIN);

        // Figures exactly as the Parking Staff dashboard and Occupancy Monitor compute them.
        $today = now()->toDateString();
        $this->command->line(sprintf(
            'Dashboard today (%s): entries %d, exits %d, denied %d, active visitors %d.',
            config('app.timezone'),
            DB::table('parking_transactions')->whereDate('entry_datetime', $today)->count(),
            DB::table('parking_transactions')->whereDate('exit_datetime', $today)->count(),
            DB::table('access_events')->whereDate('event_datetime', $today)->where('auth_result', 'denied')->count(),
            DB::table('parking_transactions')->whereNotNull('visitor_id')->whereNull('exit_datetime')->distinct()->count('visitor_id'),
        ));

        foreach (\App\Services\Parking\ParkingStats::areas() as $area) {
            $this->command->line(sprintf('%s: %d/%d occupied, %d available, %d maintenance.',
                $area['area_name'], $area['occupied'], $area['capacity'], $area['available'], $area['maintenance']));
        }

        foreach ($this->warnings as $warning) {
            $this->command->warn($warning);
        }
    }
}
