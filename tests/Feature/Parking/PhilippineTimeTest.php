<?php

namespace Tests\Feature\Parking;

use App\Models\DriverLicense;
use App\Support\CampusTime;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class PhilippineTimeTest extends ParkingTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow(); CarbonImmutable::setTestNow(); parent::tearDown();
    }

    public function test_admin_and_owner_receive_identical_unambiguous_license_times(): void
    {
        $car = $this->campusVehicle();
        $license = DriverLicense::where('driver_id', $car['driver_id'])->firstOrFail();
        $license->forceFill(['submitted_at' => '2026-10-22 00:22:00', 'checked_at' => '2026-10-22 00:23:00', 'reviewed_at' => null])->save();
        $before = (array) DB::table('driver_licenses')->where('license_id', $license->license_id)->first();
        Sanctum::actingAs($car['user']);
        $owner = $this->getJson('/api/licenses/mine')->assertOk()->json('data');
        $this->getJson('/api/licenses')->assertForbidden();
        $this->actingAsAdmin();
        $admin = $this->getJson('/api/licenses?search='.urlencode($license->license_number))->assertOk()->json('data.0');
        foreach (['submitted_at', 'checked_at', 'reviewed_at'] as $field) $this->assertSame($owner[$field], $admin[$field], $field);
        $this->assertSame('2026-10-22T00:22:00+00:00', $admin['submitted_at']);
        $this->assertSame('08:22', CarbonImmutable::parse($admin['submitted_at'])->setTimezone(CampusTime::zone())->format('H:i'));
        $this->assertSame($owner['expiry_date'], $admin['expiry_date']);
        $this->assertSame($before, (array) DB::table('driver_licenses')->where('license_id', $license->license_id)->first());
    }

    public function test_settings_expose_only_the_fixed_campus_timezone_and_audit_saves(): void
    {
        $this->actingAsAdmin();
        DB::table('system_settings')->updateOrInsert(['setting_key' => 'timezone'], ['setting_value' => 'UTC', 'setting_group' => 'general', 'data_type' => 'string', 'is_public' => false, 'updated_at' => now()]);
        $this->getJson('/api/admin/system-settings')->assertOk()->assertJsonPath('data.timezone', 'Asia/Manila');
        $this->assertDatabaseHas('system_settings', ['setting_key' => 'timezone', 'setting_value' => 'UTC']);
        $payload = ['system_name' => 'PASS', 'institution_name' => 'DYCI', 'contact_email' => 'qa@example.test', 'date_format' => 'MM/DD/YYYY', 'timezone' => 'UTC'];
        $this->putJson('/api/admin/system-settings', $payload)->assertUnprocessable()->assertJsonValidationErrors('timezone');
        $payload['timezone'] = 'Asia/Manila';
        $this->putJson('/api/admin/system-settings', $payload)->assertOk();
        $this->assertDatabaseHas('system_settings', ['setting_key' => 'timezone', 'setting_value' => 'Asia/Manila']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings_updated']);
        Sanctum::actingAs($this->user());
        $this->getJson('/api/admin/system-settings')->assertForbidden();
    }

    public function test_personal_history_filters_use_philippine_midnight_and_preserve_ownership(): void
    {
        $a = $this->campusVehicle(); $b = $this->campusVehicle();
        $area = DB::table('parking_areas')->value('parking_area_id');
        foreach (['2026-10-21 15:59:59', '2026-10-21 16:00:00', '2026-10-22 00:22:00', '2026-10-22 15:59:59', '2026-10-22 16:00:00'] as $entry) {
            DB::table('parking_transactions')->insert(['ticket_number' => (string) Str::uuid(), 'vehicle_id' => $a['vehicle_id'], 'driver_id' => $a['driver_id'],
                'user_id' => $a['user']->user_id, 'parking_area_id' => $area, 'access_method' => 'rfid', 'entry_datetime' => $entry,
                'exit_datetime' => CarbonImmutable::parse($entry)->addMinute()->toDateTimeString(), 'transaction_status' => 'completed', 'created_at' => $entry, 'updated_at' => $entry]);
        }
        DB::table('parking_transactions')->insert(['ticket_number' => (string) Str::uuid(), 'vehicle_id' => $b['vehicle_id'], 'driver_id' => $b['driver_id'],
            'user_id' => $b['user']->user_id, 'parking_area_id' => $area, 'access_method' => 'rfid', 'entry_datetime' => '2026-10-22 00:22:00',
            'exit_datetime' => '2026-10-22 00:23:00', 'transaction_status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
        Sanctum::actingAs($a['user']);
        $rows = $this->getJson('/api/parking/history?from=2026-10-22&to=2026-10-22')->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('meta.total', 3)->json('data');
        foreach ($rows as $row) {
            $this->assertSame($a['plate'], $row['plate_number']);
            $this->assertSame('2026-10-22', CarbonImmutable::parse($row['entry_datetime'])->setTimezone(CampusTime::zone())->toDateString());
        }
    }

    public function test_license_expiry_uses_the_campus_day_before_utc_midnight(): void
    {
        $at = CarbonImmutable::parse('2026-10-21T17:30:00Z'); Carbon::setTestNow($at); CarbonImmutable::setTestNow($at);
        $car = $this->campusVehicle(licenseVerification: 'pending_review');
        $license = DriverLicense::where('driver_id', $car['driver_id'])->firstOrFail();
        Sanctum::actingAs($car['user']);
        $this->post('/api/licenses', ['license_number' => $license->license_number, 'expiry_date' => '2026-10-21',
            'license_file' => UploadedFile::fake()->image('expired.png', 1200, 800)], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('expiry_date');
        $license->forceFill(['expiry_date' => '2026-10-21'])->save();
        $this->actingAsAdmin();
        $this->patchJson("/api/licenses/{$license->license_id}/review", ['decision' => 'verified', 'reason' => 'Original document inspected.'])->assertUnprocessable();
        $this->assertSame('pending_review', $license->fresh()->verification_status);
    }
}
