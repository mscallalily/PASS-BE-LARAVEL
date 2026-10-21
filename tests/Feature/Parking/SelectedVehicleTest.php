<?php
namespace Tests\Feature\Parking;

use App\Models\User;
use Database\Seeders\TwoRfidDemoSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class SelectedVehicleTest extends ParkingTestCase
{
    private function demo(): array
    {
        $this->seed(TwoRfidDemoSeeder::class);
        return [User::where('email', 'two.rfid.demo@pass.test')->firstOrFail(), DB::table('vehicles')->where('plate_number', 'DEMO-CAR-001')->value('vehicle_id'), DB::table('vehicles')->where('plate_number', 'DEMO-MC-002')->value('vehicle_id')];
    }

    public function test_only_selected_vehicle_can_enter_switch_persists_and_switching_is_locked_inside(): void
    {
        [$owner, $car, $bike] = $this->demo();
        $this->actingAsStaff();
        $denied = $this->gate('entry', ['rfid_tag_uid' => 'SIM-TWO-MC-002']);
        $this->assertSame('denied', $denied['status']);
        $this->assertStringContainsString('inactive for entry', $denied['reason']);
        $this->assertSame('denied', $this->gate('entry', ['plate_number' => 'DEMO-MC-002'])['status'], 'Manual entry cannot bypass owner selection.');
        Sanctum::actingAs($owner);
        $this->postJson("/api/vehicles/{$bike}/select-for-use")->assertOk();
        $this->assertDatabaseHas('user_vehicle_selections', ['user_id' => $owner->user_id, 'vehicle_id' => $bike]);
        $rows = collect($this->getJson('/api/vehicles')->assertOk()->json('vehicles'))->keyBy('id');
        $this->assertTrue($rows[(string) $bike]['selectedForUse']);
        $this->assertFalse($rows[(string) $car]['selectedForUse']);
        $this->actingAsStaff();
        $this->assertSame('denied', $this->gate('entry', ['rfid_tag_uid' => 'SIM-TWO-CAR-001'])['status']);
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => 'SIM-TWO-MC-002'])['status']);
        Sanctum::actingAs($owner);
        $this->postJson("/api/vehicles/{$car}/select-for-use")->assertStatus(409);
        $this->assertDatabaseHas('user_vehicle_selections', ['user_id' => $owner->user_id, 'vehicle_id' => $bike]);
        $rows = $this->getJson('/api/vehicles')->assertOk()->json('vehicles');
        $this->assertTrue($rows[0]['switchLocked']);
        $this->actingAsStaff();
        $this->assertSame('completed', $this->gate('exit', ['rfid_tag_uid' => 'SIM-TWO-MC-002'])['status']);
        Sanctum::actingAs($owner);
        $this->postJson("/api/vehicles/{$car}/select-for-use")->assertOk();
        $this->assertSame(2, DB::table('rfid_assignments')->where('user_id', $owner->user_id)->where('assignment_status', 'active')->count(), 'Switching must not revoke either tag.');
        $this->actingAsStaff();
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => 'SIM-TWO-CAR-001'])['status']);
    }

    public function test_owner_cannot_select_another_owners_pending_or_deactivated_vehicle(): void
    {
        [$owner, $car, $bike] = $this->demo();
        $other = $this->campusVehicle();
        Sanctum::actingAs($owner);
        $this->postJson('/api/vehicles/'.$other['vehicle_id'].'/select-for-use')->assertNotFound();
        DB::table('vehicle_authorizations')->where('vehicle_id', $bike)->update(['authorization_status' => 'pending']);
        $this->postJson("/api/vehicles/{$bike}/select-for-use")->assertStatus(422);
        DB::table('vehicle_authorizations')->where('vehicle_id', $bike)->update(['authorization_status' => 'approved']);
        DB::table('rfid_tags')->where('tag_uid', 'SIM-TWO-MC-002')->update(['tag_status' => 'deactivated']);
        $this->postJson("/api/vehicles/{$bike}/select-for-use")->assertStatus(422);
        $this->assertDatabaseHas('user_vehicle_selections', ['user_id' => $owner->user_id, 'vehicle_id' => $car]);
        $this->actingAsStaff();
        $this->postJson("/api/vehicles/{$car}/select-for-use")->assertForbidden();
    }
}
