<?php

namespace Tests\Feature\Parking;

use App\Jobs\ProcessVehiclePlateCheck;
use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Support\PlateNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class VehicleRegistrationFlowTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); Storage::fake('private'); Cache::flush(); Queue::fake();
        $this->mock(LicenseDocumentReader::class)->shouldNotReceive('read', 'readTextImage');
    }
    private function owner(): array
    {
        $car = $this->campusVehicle();
        $car['user']->roles()->attach(DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($car['user']); return $car;
    }
    private function payload(string $plate = '765WOR', string $type = 'Motorcycle'): array
    {
        return ['plate_number' => $plate, 'vehicle_type' => $type, 'make' => 'HONDA', 'model' => 'CLICK', 'color' => 'BLACK',
            'vehicle_photo' => UploadedFile::fake()->image('vehicle.png', 1200, 800)];
    }
    private function submit(array $data)
    {
        return $this->post('/api/vehicles', $data, ['Accept' => 'application/json']);
    }
    public function test_registration_requests_staff_review_for_correct_lot_without_reading_photo_and_keeps_rfid_separate(): void
    {
        $car = $this->owner(); $tags = DB::table('rfid_assignments')->count();
        $r = $this->submit($this->payload('765 wor') + ['authorization_status' => 'rejected', 'parking_area_id' => 999, 'owner_user_id' => 999, 'authenticity_verified' => true])->assertCreated()
            ->assertJsonPath('authorization_status', 'pending')->assertJsonPath('approval_basis', 'staff_review_required')
            ->assertJsonPath('plate_verified', false)->assertJsonPath('photo_verified', false)->assertJsonPath('authenticity_verified', false)->assertJsonPath('ownership_verified', false);
        $id = $r->json('vehicle_id'); $area = DB::table('parking_areas')->where('area_code', 'MC')->value('parking_area_id');
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $id, 'owner_user_id' => $car['user']->user_id, 'plate_number' => '765 WOR', 'color' => 'BLACK', 'make' => 'HONDA', 'model' => 'CLICK']);
        $this->assertDatabaseHas('vehicle_authorizations', ['vehicle_id' => $id, 'authorization_status' => 'pending', 'parking_area_id' => $area, 'reviewed_by' => null, 'reviewed_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vehicle_registration_submitted']);
        $this->assertDatabaseCount('rfid_assignments', $tags); $this->assertDatabaseCount('parking_transactions', 0); Queue::assertNothingPushed();
        $list = collect($this->getJson('/api/vehicles')->assertOk()->json('vehicles'))->firstWhere('id', (string) $id);
        foreach (['vehicle_photo_path', 'candidates', 'confidence'] as $key) $this->assertArrayNotHasKey($key, $list);
    }
    public function test_client_supplied_photo_proof_cannot_be_saved_as_verified(): void
    {
        $this->owner();
        $r = $this->submit($this->payload('765W0R') + ['plate_verified' => true, 'photo_verified' => true, 'evidence_verified' => true, 'plate_number_read' => '765WOR'])
            ->assertCreated()->assertJsonPath('plate_verified', false)->assertJsonPath('photo_verified', false)->assertJsonPath('approval_basis', 'staff_review_required');
        $audit = DB::table('audit_logs')->where('action', 'vehicle_registration_submitted')->orderByDesc('log_id')->first();
        $this->assertNotNull($audit);
        $this->assertFalse(json_decode($audit->new_values, true)['plate_verified']);
    }

    public function test_invalid_plate_and_missing_required_fields_do_not_save_records(): void
    {
        $this->owner(); $before = DB::table('vehicles')->count();
        foreach (['ABCDEF', '1234567', 'A1', 'ABC!1234', 'TOOLONG1234567'] as $plate) {
            Cache::flush();
            $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonValidationErrors('plate_number');
        }
        Cache::flush(); $this->submit(['plate_number' => ['BAD']])->assertUnprocessable()->assertJsonValidationErrors(['plate_number', 'vehicle_photo', 'vehicle_type']);
        $this->assertDatabaseCount('vehicles', $before); $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_registration_does_not_depend_on_tesseract_and_the_photo_is_stored_privately(): void
    {
        $this->owner(); config(['services.license_ocr.tesseract_path' => '/missing/tesseract']);
        $id = $this->submit($this->payload())->assertCreated()->json('vehicle_id');
        $path = DB::table('vehicles')->where('vehicle_id', $id)->value('vehicle_photo_path');
        Storage::disk('private')->assertExists($path);
        $this->get('/api/vehicles/'.$id.'/photo')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_invalid_file_and_missing_photo_are_validation_errors_without_orphan_uploads(): void
    {
        $this->owner(); $before = DB::table('vehicles')->count();
        $data = $this->payload(); $data['vehicle_photo'] = UploadedFile::fake()->create('not-photo.txt', 10, 'text/plain');
        $this->submit($data)->assertUnprocessable()->assertJsonValidationErrors('vehicle_photo');
        unset($data['vehicle_photo']); $this->submit($data)->assertUnprocessable()->assertJsonValidationErrors('vehicle_photo');
        $this->assertDatabaseCount('vehicles', $before); $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_misconfigured_lot_cannot_approve_a_vehicle(): void
    {
        $this->owner();
        DB::table('vehicle_types')->where('type_code', 'motorcycle')->update(['parking_area_id' => DB::table('parking_areas')->where('area_code', 'CAR')->value('parking_area_id')]);
        $this->submit($this->payload())->assertUnprocessable()->assertJsonValidationErrors('vehicle_type');
    }
    public function test_newer_unverified_license_is_not_bypassed_by_an_old_verified_license(): void
    {
        $car = $this->owner();
        DB::table('driver_licenses')->where('driver_id', $car['driver_id'])->update(['license_status' => 'expired']);
        DB::table('driver_licenses')->insert(['driver_id' => $car['driver_id'], 'license_number' => 'NEW-'.Str::upper(Str::random(12)),
            'license_type' => 'non_professional', 'license_status' => 'valid', 'verification_status' => 'pending_review',
            'expiry_date' => now()->addYears(2)->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        $this->submit($this->payload())->assertUnprocessable()->assertJsonValidationErrors('license');
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_pending_license_and_invalid_input_do_not_create_registration(): void
    {
        $car = $this->owner();
        $this->submit(['plate_number' => '<script>'])->assertUnprocessable()->assertJsonValidationErrors(['plate_number', 'vehicle_photo', 'vehicle_type']);
        DB::table('driver_licenses')->where('driver_id', $car['driver_id'])->update(['verification_status' => 'pending_review']);
        $this->submit($this->payload())->assertUnprocessable()->assertJsonValidationErrors('license');
    }
    public function test_current_license_is_rechecked_before_persisting_the_registration(): void
    {
        $car = $this->owner(); $before = DB::table('vehicles')->count(); $changed = false;
        DB::listen(function ($query) use ($car, &$changed) {
            if (!$changed && str_contains($query->sql, 'users') && str_contains($query->sql, 'for update')) {
                $changed = true; DB::table('driver_licenses')->where('driver_id', $car['driver_id'])->update(['expiry_date' => '2000-01-01']);
            }
        });
        $this->submit($this->payload())->assertUnprocessable()->assertJsonValidationErrors('license');
        $this->assertTrue($changed); $this->assertDatabaseCount('vehicles', $before); $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_owner_deactivation_during_submission_cannot_create_approved_vehicle(): void
    {
        $car = $this->owner(); $before = DB::table('vehicles')->count(); $changed = false;
        DB::listen(function ($query) use ($car, &$changed) {
            if (!$changed && str_contains($query->sql, 'users') && str_contains($query->sql, 'for update')) {
                $changed = true; DB::table('users')->where('user_id', $car['user']->user_id)->update(['account_status' => 'deactivated']);
            }
        });
        $this->submit($this->payload())->assertForbidden(); $this->assertTrue($changed);
        $this->assertDatabaseCount('vehicles', $before); $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_duplicate_separator_alias_is_rejected_without_rechecking_photo(): void
    {
        $car = $this->owner();
        $this->submit($this->payload(str_replace(' ', '-', $car['plate'])))->assertUnprocessable()->assertJsonValidationErrors('plate_number');
    }
    public function test_photo_resubmission_retains_old_photo_and_decision_and_appends_pending_review(): void
    {
        $car = $this->owner(); $old = UploadedFile::fake()->image('old.png')->store('vehicle-photos', 'private');
        DB::table('vehicles')->where('vehicle_id', $car['vehicle_id'])->update(['vehicle_photo_path' => $old]);
        DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->update(['authorization_status' => 'rejected', 'remarks' => 'Old decision']);
        $this->post('/api/vehicles/'.$car['vehicle_id'].'/photo', ['vehicle_photo' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('private')->assertExists($old);
        $this->assertDatabaseHas('vehicle_authorizations', ['vehicle_id' => $car['vehicle_id'], 'authorization_status' => 'rejected', 'remarks' => 'Old decision']);
        $this->assertDatabaseHas('vehicle_authorizations', ['vehicle_id' => $car['vehicle_id'], 'authorization_status' => 'pending']);
        $this->assertSame('pending', DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->orderByDesc('authorization_id')->value('authorization_status'));
    }
    public function test_other_owner_cannot_access_photo_and_approved_or_suspended_photo_cannot_be_replaced(): void
    {
        $car = $this->owner();
        Sanctum::actingAs($this->owner()['user']);
        $this->post('/api/vehicles/'.$car['vehicle_id'].'/photo', ['vehicle_photo' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])->assertNotFound();
        $this->getJson('/api/vehicles/'.$car['vehicle_id'].'/photo')->assertForbidden(); Sanctum::actingAs($car['user']);
        foreach (['approved', 'suspended'] as $status) {
            DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->update(['authorization_status' => $status]);
            $this->post('/api/vehicles/'.$car['vehicle_id'].'/photo', ['vehicle_photo' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])->assertStatus(409);
        }
    }
    public function test_write_budget_does_not_block_polling_and_is_scoped_to_the_owner(): void
    {
        $this->owner();
        for ($i = 0; $i < 6; $i++) $this->submit([])->assertUnprocessable();
        $this->submit([])->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('action', 'vehicle-plate-submit');
        $this->getJson('/api/sync/revision')->assertOk(); $this->getJson('/api/licenses/mine')->assertOk();
        $this->owner(); $this->submit([])->assertUnprocessable();
    }

    public function test_old_background_job_never_approves_pending_vehicle_or_changes_new_decision(): void
    {
        $car = $this->owner(); $auth = DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->first(); $run = (string) Str::uuid();
        DB::table('vehicle_authorizations')->where('authorization_id', $auth->authorization_id)->update(['authorization_status' => 'pending', 'reviewed_at' => null]);
        if (Schema::hasColumn('vehicle_authorizations', 'plate_check_status')) DB::table('vehicle_authorizations')->where('authorization_id', $auth->authorization_id)->update(['plate_check_status' => 'processing', 'plate_check_run_id' => $run]);
        $job = unserialize(serialize(new ProcessVehiclePlateCheck($auth->authorization_id, $run))); $job->handle(); $job->handle();
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $auth->authorization_id, 'authorization_status' => 'pending']);
        DB::table('vehicle_authorizations')->where('authorization_id', $auth->authorization_id)->update(['authorization_status' => 'approved', 'remarks' => 'New decision']);
        $job->failed(null); $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $auth->authorization_id, 'authorization_status' => 'approved', 'remarks' => 'New decision']);
    }

    public function test_pending_registration_cannot_select_a_vehicle_or_assign_rfid(): void
    {
        $this->owner(); $id = $this->submit($this->payload())->assertCreated()->json('vehicle_id');
        $this->assertFalse(DB::table('rfid_assignments')->where('vehicle_id', $id)->exists());
        $this->postJson('/api/vehicles/'.$id.'/select-for-use')->assertUnprocessable();
        $this->postJson('/api/rfid/assignments', ['vehicle_id' => $id, 'tag_uid' => 'UNAUTHORIZED-TAG'])->assertForbidden();
        $this->assertDatabaseCount('parking_transactions', 0);
    }

}
