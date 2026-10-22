<?php

namespace Tests\Feature\Parking;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class VehicleManualReviewTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); Storage::fake('private'); Cache::flush();
    }

    private function submission(): array
    {
        $owner = $this->campusVehicle();
        $owner['user']->roles()->attach(DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($owner['user']);
        $data = ['plate_number' => $this->nextTestPlate('REG'), 'vehicle_type' => 'Sedan', 'make' => 'TOYOTA', 'model' => 'VIOS', 'color' => 'WHITE',
            'authorization_status' => 'approved', 'reviewed_by' => 1, 'vehicle_photo' => UploadedFile::fake()->image('vehicle.png', 1000, 700)];
        $response = $this->post('/api/vehicles', $data, ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('authorization_status', 'pending');
        $id = $response->json('vehicle_id');
        return $owner + ['submitted_id' => $id, 'authorization_id' => DB::table('vehicle_authorizations')->where('vehicle_id', $id)->max('authorization_id')];
    }

    public function test_new_submission_stays_pending_until_admin_reviews_the_photo_and_approves(): void
    {
        $car = $this->submission(); $id = $car['submitted_id'];
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'pending', 'reviewed_at' => null, 'reviewed_by' => null]);
        $this->assertFalse(DB::table('rfid_assignments')->where('vehicle_id', $id)->exists());
        $admin = $this->actingAsAdmin();
        $this->get('/api/vehicles/'.$id.'/photo')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->patchJson('/api/vehicles/authorizations/'.$car['authorization_id'].'/review', ['decision' => 'approved'])->assertOk()->assertJsonPath('data.authorization_status', 'approved');
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'approved', 'reviewed_by' => $admin->user_id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'authorization_approved', 'user_id' => $admin->user_id]);
        Sanctum::actingAs($car['user']);
        $this->getJson('/api/vehicles/my-authorizations')->assertOk()->assertJsonFragment(['vehicle_id' => $id, 'authorization_status' => 'approved']);
        $this->assertFalse(DB::table('rfid_assignments')->where('vehicle_id', $id)->exists());
        $this->assertDatabaseCount('parking_transactions', 0);
    }

    public function test_rejection_and_correction_return_to_pending_with_previous_decision_retained(): void
    {
        $car = $this->submission(); $this->actingAsAdmin();
        $this->patchJson('/api/vehicles/authorizations/'.$car['authorization_id'].'/review', ['decision' => 'rejected'])->assertUnprocessable();
        $this->patchJson('/api/vehicles/authorizations/'.$car['authorization_id'].'/review', ['decision' => 'rejected', 'reason' => 'Upload a clearer vehicle photo.'])->assertOk();
        Sanctum::actingAs($car['user']);
        $this->post('/api/vehicles/'.$car['submitted_id'].'/resubmit', ['plate_number' => $this->nextTestPlate('FIX'), 'vehicle_type' => 'Motorcycle',
            'make' => 'HONDA', 'model' => 'CLICK', 'color' => 'BLACK', 'vehicle_photo' => UploadedFile::fake()->image('corrected.png')], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('authorization_status', 'pending');
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'rejected', 'remarks' => 'Upload a clearer vehicle photo.']);
        $latest = DB::table('vehicle_authorizations')->where('vehicle_id', $car['submitted_id'])->orderByDesc('authorization_id')->first();
        $this->assertGreaterThan($car['authorization_id'], $latest->authorization_id);
        $this->assertSame('pending', $latest->authorization_status); $this->assertNull($latest->reviewed_at);
        $this->assertSame(DB::table('parking_areas')->where('area_code', 'MC')->value('parking_area_id'), $latest->parking_area_id);
    }

    public function test_owner_cannot_approve_and_staff_cannot_approve_a_superseded_pending_submission(): void
    {
        $car = $this->submission();
        $url = '/api/vehicles/authorizations/'.$car['authorization_id'].'/review';
        $this->patchJson($url, ['decision' => 'approved'])->assertForbidden();
        $latest = DB::table('vehicle_authorizations')->insertGetId(['vehicle_id' => $car['submitted_id'], 'authorization_status' => 'pending', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()], 'authorization_id');
        $this->actingAsAdmin();
        $this->patchJson($url, ['decision' => 'approved'])->assertStatus(409);
        $rows = $this->getJson('/api/vehicles/authorizations')->assertOk()->json('data');
        $current = collect($rows)->where('vehicle_id', $car['submitted_id'])->values();
        $this->assertCount(1, $current); $this->assertSame($latest, $current[0]['authorization_id']);
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'pending']);
    }

    public function test_latest_pending_cannot_receive_rfid_or_enter_using_an_old_approved_authorization(): void
    {
        $car = $this->campusVehicle();
        DB::table('vehicle_authorizations')->insert(['vehicle_id' => $car['vehicle_id'], 'authorization_status' => 'pending', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAsStaff();
        $this->postJson('/api/parking/entry', ['rfid_tag_uid' => $car['tag']])->assertJsonPath('status', 'denied');
        DB::table('rfid_assignments')->where('vehicle_id', $car['vehicle_id'])->update(['assignment_status' => 'revoked', 'unassigned_at' => now()]);
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/assignments', ['vehicle_id' => $car['vehicle_id'], 'tag_uid' => 'PENDING-MUST-NOT-ISSUE'])->assertUnprocessable();
        $this->assertDatabaseMissing('rfid_tags', ['tag_uid' => 'PENDING-MUST-NOT-ISSUE']);
        $this->assertDatabaseCount('parking_transactions', 0);
    }

    public function test_manual_approval_still_requires_current_license_and_private_photo(): void
    {
        $car = $this->submission(); $this->actingAsAdmin();
        $url = '/api/vehicles/authorizations/'.$car['authorization_id'].'/review';
        DB::table('driver_licenses')->where('driver_id', $car['driver_id'])->update(['verification_status' => 'pending_review']);
        $this->patchJson($url, ['decision' => 'approved'])->assertUnprocessable();
        DB::table('driver_licenses')->where('driver_id', $car['driver_id'])->update(['verification_status' => 'verified']);
        Storage::disk('private')->delete(DB::table('vehicles')->where('vehicle_id', $car['submitted_id'])->value('vehicle_photo_path'));
        $this->patchJson($url, ['decision' => 'approved'])->assertUnprocessable();
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $car['authorization_id'], 'authorization_status' => 'pending', 'reviewed_by' => null]);
    }
}
