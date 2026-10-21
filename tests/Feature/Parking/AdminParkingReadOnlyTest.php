<?php

namespace Tests\Feature\Parking;

use App\Support\ParkingMapLayout;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class AdminParkingReadOnlyTest extends ParkingTestCase
{
    public function test_admin_can_monitor_but_cannot_mutate_live_parking_even_by_direct_api(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $waiting = $this->campusVehicle();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $transaction = $this->activeTransaction($car['vehicle_id']);
        $freeSlot = DB::table('parking_slots')->where('slot_code', 'C-03')->value('slot_id');
        $counts = $this->recordCounts();
        $statuses = DB::table('parking_slots')->orderBy('slot_id')->pluck('slot_status', 'slot_id')->all();

        foreach (['admin', 'parking_admin', 'security_with_admin_role'] as $type) {
            $admin = $this->user($type === 'admin' ? 'admin' : ($type === 'parking_admin' ? 'other' : 'security'));
            if ($type !== 'admin') {
                DB::table('user_roles')->insert([
                    'user_id' => $admin->user_id,
                    'role_id' => DB::table('roles')->where('role_code', $type === 'parking_admin' ? 'parking_admin' : 'admin')->value('role_id'),
                ]);
            }
            Sanctum::actingAs($admin);
            $this->getJson('/api/parking/map')->assertOk();
            $this->getJson('/api/parking/availability-map')->assertOk();
            $this->getJson('/api/parking/overview')->assertOk();
            $this->postJson('/api/parking/entry', ['rfid_tag_uid' => $waiting['tag']])->assertForbidden();
            $this->postJson('/api/parking/exit', ['rfid_tag_uid' => $car['tag']])->assertForbidden();
            $this->postJson('/api/parking/transactions/'.$transaction->transaction_id.'/observed-slot', ['slot_id' => $entry['slot_id']])->assertForbidden();
            $this->patchJson('/api/parking/slots/'.$freeSlot, ['slot_status' => 'maintenance'])->assertForbidden();
            $this->assertSame($counts, $this->recordCounts());
            $this->assertSame($statuses, DB::table('parking_slots')->orderBy('slot_id')->pluck('slot_status', 'slot_id')->all());
            $this->assertEquals($transaction, $this->activeTransaction($car['vehicle_id']));
            $this->assertNull($this->activeTransaction($waiting['vehicle_id']));
        }
    }

    public function test_parking_staff_retains_gate_confirmation_and_maintenance_workflows(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $freeSlot = DB::table('parking_slots')->where('slot_code', 'C-03')->value('slot_id');
        $this->patchJson('/api/parking/slots/'.$freeSlot, ['slot_status' => 'maintenance'])->assertOk();
        $this->assertDatabaseHas('parking_slots', ['slot_id' => $freeSlot, 'slot_status' => 'maintenance']);
        $this->patchJson('/api/parking/slots/'.$freeSlot, ['slot_status' => 'available'])->assertOk();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->assertSame('granted', $entry['status']);
        $transaction = $this->activeTransaction($car['vehicle_id']);
        $this->postJson('/api/parking/transactions/'.$transaction->transaction_id.'/observed-slot', ['slot_id' => $entry['slot_id']])->assertOk();
        $this->assertNotNull($this->activeTransaction($car['vehicle_id'])->slot_confirmed_at);
        $this->assertSame('completed', $this->gate('exit', ['rfid_tag_uid' => $car['tag']])['status']);
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
    }

    public function test_measurements_expose_a_human_readable_upper_junction_label(): void
    {
        $dimensions = ParkingMapLayout::geometry(true)['dimensions'];
        $upper = collect($dimensions)->firstWhere('id', 'upper-junction');
        $this->assertSame('Upper junction', $upper['label']);
        $this->assertSame('3.36 m', $upper['text']);
        $this->assertStringNotContainsString('PINK-TOP', json_encode($dimensions));
        $this->assertSame('2.60 m', collect($dimensions)->firstWhere('id', 'C3')['text']);
    }

    private function recordCounts(): array
    {
        return array_map(fn ($table) => DB::table($table)->count(), ['parking_transactions', 'access_events', 'audit_logs', 'parking_violations']);
    }
}
