<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class VehicleReleaseTest extends ParkingTestCase
{
    private function parked(): array
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $car['transaction_id'] = $this->gate('entry', ['rfid_tag_uid' => $car['tag']])['transaction_id'];
        return $car;
    }

    private function payload(array $car): array
    {
        return ['transaction_id' => $car['transaction_id'], 'driver_name' => 'Paolo Substitute', 'driver_contact' => '09171234567', 'license_number' => 'N01-23-456789', 'license_expiry' => now()->addYear()->toDateString(), 'reason' => 'Owner is unwell and needs help.', 'acknowledged' => true];
    }

    public function test_owner_request_staff_verification_single_exit_and_no_reuse(): void
    {
        $car = $this->parked();
        Sanctum::actingAs($car['user']);
        $id = $this->postJson('/api/vehicle-releases', $this->payload($car))->assertCreated()->json('release_id');
        $stored = DB::table('vehicle_release_requests')->where('release_id', $id)->first();
        $this->assertStringNotContainsString('N01-23-456789', $stored->license_encrypted);
        $this->getJson('/api/vehicle-releases')->assertOk()->assertJsonPath('data.0.license_reference', '•••• 6789');
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'release', 'identity_checked' => true, 'authorization_checked' => true])->assertForbidden();
        $staff = $this->actingAsStaff();
        $this->assertSame('denied', $this->gate('exit', ['rfid_tag_uid' => $car['tag']])['status']);
        $this->assertNotNull($this->activeTransaction($car['vehicle_id']));
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'release'])->assertStatus(422);
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'release', 'identity_checked' => true])->assertStatus(422);
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'release', 'identity_checked' => true, 'authorization_checked' => true])->assertOk();
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
        $this->assertDatabaseHas('vehicle_release_requests', ['release_id' => $id, 'status' => 'used', 'released_by' => $staff->user_id]);
        $this->assertNotNull(DB::table('vehicle_release_requests')->where('release_id', $id)->value('exit_event_id'));
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'release', 'identity_checked' => true])->assertStatus(409);
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => $car['tag']])['status']);
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'release', 'identity_checked' => true])->assertStatus(409);
        $this->assertNotNull($this->activeTransaction($car['vehicle_id']));
        $this->assertDatabaseHas('vehicle_driver', ['vehicle_id' => $car['vehicle_id'], 'driver_id' => $car['driver_id']]);
    }

    public function test_ownership_scope_duplicate_expiry_and_cancel(): void
    {
        $car = $this->parked();
        Sanctum::actingAs($this->user());
        $this->postJson('/api/vehicle-releases', $this->payload($car))->assertForbidden();
        Sanctum::actingAs($car['user']);
        $id = $this->postJson('/api/vehicle-releases', $this->payload($car))->assertCreated()->json('release_id');
        $this->postJson('/api/vehicle-releases', $this->payload($car))->assertStatus(409);
        Sanctum::actingAs($this->user());
        $this->getJson('/api/vehicle-releases')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'cancel'])->assertForbidden();
        Sanctum::actingAs($car['user']);
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'cancel'])->assertOk();
        $new = $this->postJson('/api/vehicle-releases', $this->payload($car))->assertCreated()->json('release_id');
        $this->travel(121)->minutes();
        $this->actingAsStaff();
        $this->postJson("/api/vehicle-releases/{$new}/action", ['action' => 'release', 'identity_checked' => true, 'authorization_checked' => true])->assertStatus(409);
        $this->assertSame('completed', $this->gate('exit', ['rfid_tag_uid' => $car['tag']])['status'], 'Expired request must not trap a normal driver.');
    }

    public function test_staff_and_admin_cannot_authorize_for_an_unavailable_owner(): void
    {
        $car = $this->parked();
        $this->postJson('/api/vehicle-releases', $this->payload($car))->assertForbidden();
        $this->actingAsAdmin();
        $this->postJson('/api/vehicle-releases', $this->payload($car))->assertForbidden();
        $this->postJson('/api/vehicle-releases', $this->payload($car) + ['exception_reason' => 'Owner unavailable; administrator attempts override.'])->assertStatus(422);
        Sanctum::actingAs($car['user']);
        $id = $this->postJson('/api/vehicle-releases', $this->payload($car))->assertCreated()->json('release_id');
        $this->actingAsAdmin();
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'approve'])->assertStatus(422);
        $this->postJson("/api/vehicle-releases/{$id}/action", ['action' => 'release', 'identity_checked' => true])->assertStatus(422);
        $this->assertNotNull($this->activeTransaction($car['vehicle_id']));
    }
}
