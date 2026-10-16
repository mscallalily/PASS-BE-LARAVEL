<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/** Synthetic local fixtures only. Does not create any parking entries. */
class TwoRfidDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) throw new RuntimeException('TwoRfidDemoSeeder is only available in local/testing.');
        DB::transaction(function () {
            $existing = User::withTrashed()->where('email', 'two.rfid.demo@pass.test')->first();
            if ($existing) {
                if ($existing->user_code !== 'TWO-RFID-DEMO-001') throw new RuntimeException('Demo email is already in use by a different account.');
                $this->command?->warn('Demo already exists. Existing account, tags and parking sessions were not changed.');
                return;
            }
            $role = DB::table('roles')->where('role_code', 'student')->where('is_active', true)->value('role_id');
            if (!$role) throw new RuntimeException('Run RoleSeeder first.');
            foreach (['sedan', 'motorcycle'] as $type) {
                if (!DB::table('vehicle_types')->where('type_code', $type)->exists()) throw new RuntimeException('Run VehicleTypeSeeder first.');
            }
            foreach ([['DEMO-CAR-001', 'SIM-TWO-CAR-001'], ['DEMO-MC-002', 'SIM-TWO-MC-002']] as [$plate, $tag]) {
                if (DB::table('vehicles')->where('plate_number', $plate)->exists() || DB::table('rfid_tags')->where('tag_uid', $tag)->exists()) throw new RuntimeException('A demo plate or tag is already in use. Nothing was changed.');
            }
            $password = Str::random(24);
            $owner = DB::table('users')->insertGetId(['user_code' => 'TWO-RFID-DEMO-001', 'email' => 'two.rfid.demo@pass.test', 'password' => Hash::make($password), 'user_type' => 'student', 'account_status' => 'active', 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()], 'user_id');
            DB::table('user_profiles')->insert(['user_id' => $owner, 'first_name' => 'Two RFID', 'last_name' => 'Demo Owner', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('user_roles')->insert(['user_id' => $owner, 'role_id' => $role, 'assigned_at' => now()]);
            $driver = DB::table('drivers')->insertGetId(['user_id' => $owner, 'first_name' => 'Two RFID', 'last_name' => 'Demo Owner', 'driver_status' => 'active', 'created_at' => now(), 'updated_at' => now()], 'driver_id');
            DB::table('driver_licenses')->insert(['driver_id' => $driver, 'license_number' => 'D00-00-000001', 'license_type' => 'non_professional', 'license_status' => 'valid', 'verification_status' => 'verified', 'expiry_date' => now()->addYears(3)->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ([['sedan', 'DEMO-CAR-001', 'SIM-TWO-CAR-001'], ['motorcycle', 'DEMO-MC-002', 'SIM-TWO-MC-002']] as [$type, $plate, $tag]) {
                $vehicle = DB::table('vehicles')->insertGetId(['owner_user_id' => $owner, 'vehicle_type_id' => DB::table('vehicle_types')->where('type_code', $type)->value('vehicle_type_id'), 'plate_number' => $plate, 'registration_status' => 'registered', 'created_at' => now(), 'updated_at' => now()], 'vehicle_id');
                DB::table('vehicle_driver')->insert(['vehicle_id' => $vehicle, 'driver_id' => $driver, 'relationship' => 'owner', 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('vehicle_authorizations')->insert(['vehicle_id' => $vehicle, 'authorization_status' => 'approved', 'valid_from' => now()->subDay()->toDateString(), 'valid_until' => now()->addYear()->toDateString(), 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                $rfid = DB::table('rfid_tags')->insertGetId(['tag_uid' => $tag, 'tag_type' => 'card', 'tag_status' => 'assigned', 'created_at' => now(), 'updated_at' => now()], 'rfid_id');
                DB::table('rfid_assignments')->insert(['rfid_id' => $rfid, 'user_id' => $owner, 'vehicle_id' => $vehicle, 'assigned_at' => now(), 'valid_until' => now()->addYear()->toDateString(), 'assignment_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->command?->info('Synthetic demo created. No vehicles are inside yet.');
            $this->command?->info('Optional owner login: two.rfid.demo@pass.test');
            $this->command?->info('Generated demo password (shown once): '.$password);
        });
        $this->command?->info('Car RFID: SIM-TWO-CAR-001 | Motorcycle RFID: SIM-TWO-MC-002 | Same owner.');
    }
}
