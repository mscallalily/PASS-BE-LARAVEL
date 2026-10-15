<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class RfidAssignmentTest extends ParkingTestCase
{
    private function withoutTag(array $fixture): array
    {
        DB::table('rfid_assignments')->where('vehicle_id', $fixture['vehicle_id'])->update(['assignment_status' => 'revoked', 'unassigned_at' => now()]);
        DB::table('rfid_tags')->where('tag_uid', $fixture['tag'])->update(['tag_status' => 'deactivated']);
        return $fixture;
    }

    public function test_admin_assigns_tag_to_owner_without_creating_parking_entry(): void
    {
        $vehicle = $this->withoutTag($this->campusVehicle());
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/assignments', ['vehicle_id' => $vehicle['vehicle_id'], 'tag_uid' => 'TEST-ISSUED-001'])->assertCreated();
        $this->assertDatabaseHas('rfid_assignments', ['vehicle_id' => $vehicle['vehicle_id'], 'user_id' => $vehicle['user']->user_id, 'assignment_status' => 'active']);
        $this->assertDatabaseHas('rfid_tags', ['tag_uid' => 'TEST-ISSUED-001', 'tag_status' => 'assigned']);
        $this->assertDatabaseCount('parking_transactions', 0);
    }

    public function test_student_and_parking_staff_cannot_assign_tags(): void
    {
        Sanctum::actingAs($this->user());
        $this->postJson('/api/rfid/assignments', [])->assertForbidden();
        $this->actingAsStaff();
        $this->postJson('/api/rfid/assignments', [])->assertForbidden();
    }

    public function test_pending_vehicle_or_unverified_license_cannot_receive_tag(): void
    {
        $pending = $this->withoutTag($this->campusVehicle(authorization: 'pending'));
        $unverified = $this->withoutTag($this->campusVehicle(licenseVerification: 'pending_review'));
        $this->actingAsAdmin();
        foreach ([$pending, $unverified] as $index => $vehicle) {
            $this->postJson('/api/rfid/assignments', ['vehicle_id' => $vehicle['vehicle_id'], 'tag_uid' => 'TEST-BLOCKED-'.$index])->assertUnprocessable();
        }
        $this->assertDatabaseMissing('rfid_tags', ['tag_uid' => 'TEST-BLOCKED-0']);
        $this->assertDatabaseMissing('rfid_tags', ['tag_uid' => 'TEST-BLOCKED-1']);
    }

    public function test_duplicate_vehicle_and_tag_assignments_are_rejected(): void
    {
        $assigned = $this->campusVehicle();
        $free = $this->withoutTag($this->campusVehicle());
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/assignments', ['vehicle_id' => $assigned['vehicle_id'], 'tag_uid' => 'TEST-DUPLICATE'])->assertUnprocessable();
        $this->postJson('/api/rfid/assignments', ['vehicle_id' => $free['vehicle_id'], 'tag_uid' => $assigned['tag']])->assertUnprocessable();
    }

    public function test_revocation_retains_history_and_blocks_future_entry(): void
    {
        $vehicle = $this->campusVehicle();
        $assignment = DB::table('rfid_assignments')->where('vehicle_id', $vehicle['vehicle_id'])->first();
        $this->actingAsAdmin();
        $this->patchJson('/api/rfid/assignments/'.$assignment->assignment_id.'/revoke')->assertOk();
        $this->assertDatabaseHas('rfid_assignments', ['assignment_id' => $assignment->assignment_id, 'assignment_status' => 'revoked']);
        $this->assertDatabaseHas('rfid_tags', ['tag_uid' => $vehicle['tag'], 'tag_status' => 'deactivated']);
        $this->postJson('/api/parking/entry', ['rfid_tag_uid' => $vehicle['tag']])->assertJsonPath('status', 'denied');
        $this->assertDatabaseCount('parking_transactions', 0);
    }
}
