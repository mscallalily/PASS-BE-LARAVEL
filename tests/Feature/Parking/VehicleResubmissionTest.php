<?php

namespace Tests\Feature\Parking;

use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Support\PlateNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class VehicleResubmissionTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); Storage::fake('private'); Cache::flush();
        $this->mock(LicenseDocumentReader::class)->shouldNotReceive('read', 'readTextImage');
    }

    private function rejected(): array
    {
        $car = $this->campusVehicle();
        $car['user']->roles()->attach(DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        $car['photo'] = UploadedFile::fake()->image('previous.png', 1000, 700)->store('vehicle-photos', 'private');
        DB::table('vehicles')->where('vehicle_id', $car['vehicle_id'])->update(['vehicle_photo_path' => $car['photo']]);
        DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->update(['authorization_status' => 'rejected', 'remarks' => 'Please correct the plate and vehicle photo.']);
        $car['authorization_id'] = DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->value('authorization_id');
        DB::table('rfid_assignments')->where('vehicle_id', $car['vehicle_id'])->update(['assignment_status' => 'revoked', 'unassigned_at' => now()]);
        Sanctum::actingAs($car['user']);
        return $car;
    }


    private function payload(string $plate = 'ABC9876', string $type = 'Motorcycle'): array
    {
        return ['plate_number' => $plate, 'vehicle_type' => $type, 'make' => 'honda', 'model' => 'click',
            'color' => 'black', 'year_model' => 2025, 'vehicle_photo' => UploadedFile::fake()->image('corrected.png', 1200, 800)];
    }

    private function submit(int $id, array $data)
    {
        return $this->post('/api/vehicles/'.$id.'/resubmit', $data, ['Accept' => 'application/json']);
    }

    public function test_owner_can_correct_rejected_record_without_duplicate_vehicle_and_preserves_history(): void
    {
        $car = $this->rejected(); $vehicles = DB::table('vehicles')->count(); $tags = DB::table('rfid_assignments')->count();
        $this->submit($car['vehicle_id'], $this->payload() + ['owner_user_id' => 999, 'authorization_status' => 'rejected'])
            ->assertOk()->assertJsonPath('vehicle_id', $car['vehicle_id'])->assertJsonPath('authorization_status', 'pending')
            ->assertJsonPath('approval_basis', 'staff_review_required')->assertJsonPath('plate_verified', false)
            ->assertJsonPath('photo_verified', false)->assertJsonPath('authenticity_verified', false)->assertJsonPath('ownership_verified', false);
        $this->assertDatabaseCount('vehicles', $vehicles);
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $car['vehicle_id'], 'owner_user_id' => $car['user']->user_id,
            'plate_number' => 'ABC9876', 'make' => 'HONDA', 'model' => 'CLICK', 'color' => 'BLACK']);
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'rejected', 'remarks' => 'Please correct the plate and vehicle photo.']);
        $latest = DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->orderByDesc('authorization_id')->first();
        $this->assertGreaterThan($car['authorization_id'], $latest->authorization_id);
        $this->assertSame('pending', $latest->authorization_status);
        $this->assertNull($latest->reviewed_at); $this->assertNull($latest->reviewed_by);
        $this->assertSame(DB::table('parking_areas')->where('area_code', 'MC')->value('parking_area_id'), $latest->parking_area_id);
        Storage::disk('private')->assertExists($car['photo']);
        $newPhoto = DB::table('vehicles')->where('vehicle_id', $car['vehicle_id'])->value('vehicle_photo_path');
        $this->assertNotSame($car['photo'], $newPhoto); Storage::disk('private')->assertExists($newPhoto);
        $this->assertDatabaseCount('rfid_assignments', $tags); $this->assertDatabaseCount('parking_transactions', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vehicle_resubmitted', 'auditable_id' => $car['vehicle_id'], 'user_id' => $car['user']->user_id]);
        $history = DB::table('record_history')->where('record_type', 'vehicles')->where('record_id', $car['vehicle_id'])->orderByDesc('revision_id')->first();
        $this->assertNotNull($history); $this->assertSame('updated', $history->operation);
        $this->assertStringContainsString($car['plate'], $history->before_values);
        $row = collect($this->getJson('/api/vehicles')->assertOk()->json('vehicles'))->firstWhere('id', (string) $car['vehicle_id']);
        $this->assertSame('pending', $row['authorizationStatus']); $this->assertNull($row['rejectionReason']);
        $this->assertTrue($row['canResubmit']); $this->assertNull($row['rfid']);
        foreach (['old_record', 'vehicle_photo_path', 'confidence', 'candidates'] as $key) $this->assertArrayNotHasKey($key, $row);
        $this->getJson('/api/vehicles/my-authorizations')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.authorization_status', 'pending');
    }

    public function test_unchanged_own_plate_is_allowed_and_pending_submissions_can_be_corrected(): void
    {
        $car = $this->rejected();
        DB::table('vehicle_authorizations')->where('authorization_id', $car['authorization_id'])->update(['authorization_status' => 'pending']);
        $this->submit($car['vehicle_id'], $this->payload($car['plate'], 'SUV'))->assertOk();
        $latest = DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->orderByDesc('authorization_id')->first();
        $this->assertSame(DB::table('parking_areas')->where('area_code', 'CAR')->value('parking_area_id'), $latest->parking_area_id);
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'pending']);
    }

    public function test_rejected_reason_is_visible_to_owner_but_other_users_cannot_resubmit(): void
    {
        $car = $this->rejected();
        $row = collect($this->getJson('/api/vehicles')->assertOk()->json('vehicles'))->firstWhere('id', (string) $car['vehicle_id']);
        $this->assertSame('Please correct the plate and vehicle photo.', $row['rejectionReason']); $this->assertTrue($row['canResubmit']);
        $other = $this->rejected();
        $this->submit($car['vehicle_id'], $this->payload())->assertNotFound();
        foreach (['security', 'admin', 'other'] as $type) {
            Sanctum::actingAs($this->user($type));
            $this->submit($car['vehicle_id'], $this->payload())->assertForbidden();
        }
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'rejected']);
    }

    public function test_invalid_fields_or_missing_photo_keep_original_fields_decision_and_files(): void
    {
        $car = $this->rejected(); $count = DB::table('vehicle_authorizations')->count();
        $bad = $this->payload('1234567');
        $this->submit($car['vehicle_id'], $bad)->assertUnprocessable()->assertJsonValidationErrors('plate_number');
        $good = $this->payload(); unset($good['vehicle_photo']);
        $this->submit($car['vehicle_id'], $good)->assertUnprocessable()->assertJsonValidationErrors('vehicle_photo');
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $car['vehicle_id'], 'plate_number' => $car['plate'], 'vehicle_photo_path' => $car['photo']]);
        $this->assertDatabaseCount('vehicle_authorizations', $count);
        $this->assertSame([$car['photo']], Storage::disk('private')->allFiles());
    }

    public function test_duplicate_normalized_plate_is_rejected_before_ocr_including_archived_records(): void
    {
        $car = $this->rejected(); $other = $this->campusVehicle();
        DB::table('vehicles')->where('vehicle_id', $other['vehicle_id'])->update(['plate_number' => 'XYZ 1234', 'deleted_at' => now()]);
       
        $this->submit($car['vehicle_id'], $this->payload('XYZ-1234'))->assertUnprocessable()->assertJsonValidationErrors('plate_number');
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $car['vehicle_id'], 'plate_number' => $car['plate']]);
    }

    public function test_approved_suspended_revoked_and_expired_records_cannot_use_correction_flow(): void
    {
        $car = $this->rejected();
        foreach (['approved', 'suspended', 'revoked', 'expired'] as $status) {
            DB::table('vehicle_authorizations')->where('authorization_id', $car['authorization_id'])->update(['authorization_status' => $status]);
            $this->submit($car['vehicle_id'], $this->payload())->assertStatus(409);
        }
        $this->assertSame([$car['photo']], Storage::disk('private')->allFiles());
    }

    public function test_existing_rfid_requires_staff_release_before_correction(): void
    {
        $car = $this->rejected();
        DB::table('rfid_assignments')->where('vehicle_id', $car['vehicle_id'])->update(['assignment_status' => 'active', 'unassigned_at' => null]);
       
        $this->submit($car['vehicle_id'], $this->payload())->assertStatus(409)->assertSee('existing RFID assignment');
        $this->assertDatabaseHas('rfid_assignments', ['vehicle_id' => $car['vehicle_id'], 'assignment_status' => 'active']);
    }

    public function test_vehicle_inside_cannot_be_changed_even_if_its_authorization_was_rejected(): void
    {
        $car = $this->rejected();
        DB::table('vehicle_authorizations')->where('authorization_id', $car['authorization_id'])->update(['authorization_status' => 'approved']);
        DB::table('rfid_assignments')->where('vehicle_id', $car['vehicle_id'])->update(['assignment_status' => 'active', 'unassigned_at' => null]);
        $this->actingAsStaff(); $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        DB::table('vehicle_authorizations')->where('authorization_id', $car['authorization_id'])->update(['authorization_status' => 'rejected']);
        Sanctum::actingAs($car['user']);
        $this->submit($car['vehicle_id'], $this->payload())->assertStatus(409)->assertSee('exit');
        $this->assertNotNull($this->activeTransaction($car['vehicle_id']));
    }

    public function test_authorization_changed_during_submission_is_not_overwritten_and_new_upload_is_cleaned(): void
    {
        $car = $this->rejected();
        $changed = false;
        DB::listen(function ($query) use ($car, &$changed) {
            if (!$changed && str_contains($query->sql, 'users') && str_contains($query->sql, 'for update')) {
                $changed = true; DB::table('vehicle_authorizations')->where('authorization_id', $car['authorization_id'])->update(['authorization_status' => 'suspended']);
            }
        });
        $this->submit($car['vehicle_id'], $this->payload())->assertStatus(409);
        $this->assertTrue($changed);
        // The injected change uses this transaction; rejecting the stale request rolls it back too.
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'rejected']);
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $car['vehicle_id'], 'plate_number' => $car['plate']]);
        $this->assertSame([$car['photo']], Storage::disk('private')->allFiles());
    }

    public function test_license_changed_during_submission_does_not_save_corrected_record(): void
    {
        $car = $this->rejected();
        $changed = false;
        DB::listen(function ($query) use ($car, &$changed) {
            if (!$changed && str_contains($query->sql, 'users') && str_contains($query->sql, 'for update')) {
                $changed = true; DB::table('driver_licenses')->where('driver_id', $car['driver_id'])->update(['verification_status' => 'pending_review']);
            }
        });
        $this->submit($car['vehicle_id'], $this->payload())->assertUnprocessable()->assertJsonValidationErrors('license');
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $car['vehicle_id'], 'plate_number' => $car['plate']]);
        $this->assertSame([$car['photo']], Storage::disk('private')->allFiles());
    }

    public function test_invalid_input_saves_nothing_and_pending_updates_retain_history_without_duplicate_vehicle(): void
    {
        $car = $this->rejected(); $before = DB::table('vehicle_authorizations')->count();
        $this->submit($car['vehicle_id'], ['plate_number' => '<script>'])->assertUnprocessable()->assertJsonValidationErrors(['plate_number', 'vehicle_type', 'vehicle_photo']);
        $this->assertDatabaseCount('vehicle_authorizations', $before);
        $this->submit($car['vehicle_id'], $this->payload())->assertOk();
        $this->submit($car['vehicle_id'], $this->payload())->assertOk()->assertJsonPath('authorization_status', 'pending');
        $this->assertSame(1, DB::table('vehicles')->where('vehicle_id', $car['vehicle_id'])->count());
        $this->assertDatabaseCount('vehicle_authorizations', $before + 2);
        $this->assertCount(3, Storage::disk('private')->allFiles());
    }

}
