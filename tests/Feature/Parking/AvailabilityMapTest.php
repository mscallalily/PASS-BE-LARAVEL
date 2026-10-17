<?php

namespace Tests\Feature\Parking;

use App\Services\Parking\ParkingLayoutUpdater;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class AvailabilityMapTest extends ParkingTestCase
{
    public function test_signed_in_map_shares_geometry_and_status_but_excludes_personal_records(): void
    {
        app(ParkingLayoutUpdater::class)->apply();
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $staff = $this->getJson('/api/parking/map')->assertOk()->json();
        foreach (['student', 'faculty', 'employee', 'other'] as $type) {
            Sanctum::actingAs($this->user($type));
            $response = $this->getJson('/api/parking/availability-map')->assertOk();
            $map = $response->json();
            $this->assertSame($staff['geometry'], $map['geometry']);
            $this->assertSame($staff['measured_layout'], $map['measured_layout']);
            $this->assertCount(116, $map['measured_layout']['positions']);
            foreach ($map['measured_layout']['positions'] as $position) {
                $this->assertEqualsCanonicalizing(['x', 'y', 'w', 'h', 'rotation'], array_keys($position));
            }
            $this->assertSame([], $map['geometry']['trays']);
            $this->assertSame($staff['overall'], $map['overall']);
            $this->assertCount(139, $map['slots']);
            $this->assertCount(0, array_filter($map['slots'], fn ($slot) => ! $slot['placed']));
            $this->assertSame('occupied', collect($map['slots'])->firstWhere('slot_code', $entry['slot_code'])['status']);
            $this->assertStringNotContainsString($car['plate'], $response->getContent());
            foreach ($map['slots'] as $slot) {
                $this->assertEqualsCanonicalizing(['slot_id', 'slot_code', 'parking_area_id', 'area_code', 'area_name', 'slot_status', 'status', 'placed', 'x', 'y', 'w', 'h', 'rotation', 'accessible'], array_keys($slot));
            }
            $this->getJson('/api/parking/map')->assertForbidden();
            $this->patchJson('/api/parking/slots/'.$entry['slot_id'], ['slot_status' => 'maintenance'])->assertForbidden();
        }
        $this->actingAsStaff();
        $this->gate('exit', ['rfid_tag_uid' => $car['tag']]);
        Sanctum::actingAs($this->user());
        $this->assertSame('available', collect($this->getJson('/api/parking/availability-map')->assertOk()->json('slots'))->firstWhere('slot_code', $entry['slot_code'])['status']);
    }

    public function test_last_car_slot_is_visible_and_live_through_entry_refresh_and_exit(): void
    {
        app(ParkingLayoutUpdater::class)->apply();
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $carIds = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')->where('a.area_code', 'CAR')->pluck('s.slot_id');
        DB::table('parking_slots')->whereIn('slot_id', $carIds)->where('slot_code', '!=', 'C-55')->update(['slot_status' => 'maintenance']);
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->assertSame('C-55', $entry['slot_code']);
        $before = $this->activeTransaction($car['vehicle_id']);
        app(ParkingLayoutUpdater::class)->apply();
        $this->assertEquals($before, $this->activeTransaction($car['vehicle_id']));
        Sanctum::actingAs($this->user());
        $map = $this->getJson('/api/parking/availability-map')->assertOk()->json();
        $last = collect($map['slots'])->firstWhere('slot_code', 'C-55');
        $this->assertTrue($last['placed']);
        $this->assertSame('occupied', $last['status']);
        $this->assertSame(55, collect($map['slots'])->where('area_code', 'CAR')->where('placed', true)->count());
        $this->assertArrayNotHasKey('C-55', $map['measured_layout']['positions']);
        $this->assertSame($last['slot_id'], $entry['slot_id']);
        $this->actingAsStaff();
        $this->gate('exit', ['rfid_tag_uid' => $car['tag']]);
        Sanctum::actingAs($this->user());
        $this->assertSame('available', collect($this->getJson('/api/parking/availability-map')->assertOk()->json('slots'))->firstWhere('slot_code', 'C-55')['status']);
    }

    public function test_public_unverified_pending_and_inactive_accounts_cannot_read_the_map(): void
    {
        $this->getJson('/api/parking/availability-map')->assertUnauthorized();
        foreach ([['email_verified_at' => null], ['account_status' => 'pending'], ['account_status' => 'suspended']] as $state) {
            Sanctum::actingAs($this->user('student', $state));
            $this->getJson('/api/parking/availability-map')->assertForbidden();
        }
    }
}
