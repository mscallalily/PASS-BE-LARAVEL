<?php

namespace Tests\Feature\Parking;

use App\Support\ParkingMapLayout;
use Illuminate\Support\Facades\DB;

class ParkingLayoutExportTest extends ParkingTestCase
{
    public function test_car_numbering_follows_each_row_without_an_isolated_42(): void
    {
        $bays = array_values(array_filter(ParkingMapLayout::bays(true), fn ($b) => $b['area'] === 'CAR'));
        $this->assertSame(range(1, 45), array_column($bays, 'slot_number'));
        $north = array_values(array_filter($bays, fn ($b) => $b['row'] === 'North perimeter'));
        $this->assertSame(range(1, 18), array_column($north, 'slot_number'));
        $this->assertSame('South row', $bays[41]['row']);
        $this->assertSame(139, DB::table('parking_slots')->count());
    }

    public function test_admin_export_matches_measured_map_and_contains_no_live_identity_data(): void
    {
        $car = $this->campusVehicle(); $this->actingAsStaff(); $this->gate('entry', ['rfid_tag_uid' => $car['tag']]); $this->actingAsAdmin();
        $r = $this->getJson('/api/admin/parking-layout/export')->assertOk()->assertDontSee($car['plate'])->assertDontSee($car['tag'])->assertDontSee($car['user']->email);
        $this->assertCount(139, $r->json('slots'));
        $this->assertCount(129, array_filter($r->json('slots'), fn ($s) => $s['placed']));
        $this->assertEquals(ParkingMapLayout::geometry(true), $r->json('geometry'));
        $this->assertEquals(ParkingMapLayout::measuredLayout()['positions'], $r->json('measured_layout.positions'));
        foreach ($r->json('slots') as $slot) { $this->assertNull($slot['occupant']); $this->assertNull($slot['incident']); }
        $this->assertNotNull($this->activeTransaction($car['vehicle_id']));
    }
    public function test_regular_and_guest_maps_use_the_same_numbered_geometry_as_the_admin_export(): void
    {
        $this->actingAsAdmin(); $export = $this->getJson('/api/admin/parking-layout/export')->assertOk()->json();
        foreach (['student', 'employee', 'other'] as $type) {
            $u = $this->user($type);
            if ($type === 'other') $u->roles()->attach(DB::table('roles')->where('role_code', 'guest')->value('role_id'), ['assigned_at' => now()]);
            \Laravel\Sanctum\Sanctum::actingAs($u);
            $map = $this->getJson('/api/parking/availability-map')->assertOk()->json();
            $this->assertEquals($export['geometry'], $map['measured_layout']['geometry']);
            $this->assertEquals($export['measured_layout']['positions'], $map['measured_layout']['positions']);
        }
    }

}
