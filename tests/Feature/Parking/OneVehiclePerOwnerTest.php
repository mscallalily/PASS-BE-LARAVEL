<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;

class OneVehiclePerOwnerTest extends ParkingTestCase
{
    public function test_rfid_owner_cannot_park_two_vehicles_even_in_different_areas_and_can_switch_after_exit(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $bike = $this->campusVehicle('motorcycle');
        DB::table('vehicles')->where('vehicle_id', $bike['vehicle_id'])->update(['owner_user_id' => $car['user']->user_id]);
        DB::table('rfid_assignments')->where('vehicle_id', $bike['vehicle_id'])->update(['user_id' => $car['user']->user_id]);
        DB::table('vehicle_driver')->where('vehicle_id', $bike['vehicle_id'])->update(['driver_id' => $car['driver_id']]);

        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => $car['tag']])['status']);
        $this->assertSame('already_inside', $this->gate('entry', ['rfid_tag_uid' => $car['tag']])['status']);
        $count = DB::table('parking_transactions')->where('transaction_status', 'active')->count();
        $denied = $this->gate('entry', ['rfid_tag_uid' => $bike['tag']]);
        $this->assertSame('denied', $denied['status']);
        $this->assertStringContainsString('Another vehicle under this account', $denied['reason']);
        $this->assertSame($count, DB::table('parking_transactions')->where('transaction_status', 'active')->count());
        $this->assertNull($this->activeTransaction($bike['vehicle_id']));
        $this->assertDatabaseHas('access_events', ['event_id' => $denied['access_event_id'], 'auth_result' => 'denied']);

        $other = $this->campusVehicle();
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => $other['tag']])['status']);
        $this->assertSame('completed', $this->gate('exit', ['rfid_tag_uid' => $car['tag']])['status']);
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => $bike['tag']])['status']);
    }

    private function passFor(array $vehicle): string
    {
        $requestId = DB::table('visitor_requests')->insertGetId([
            'visitor_id' => $vehicle['visitor_id'], 'vehicle_id' => $vehicle['vehicle_id'],
            'requested_valid_from' => now()->subHour(), 'requested_valid_until' => now()->addDay(),
            'request_status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ], 'request_id');
        return $this->rawPass(['request_id' => $requestId, 'visitor_id' => $vehicle['visitor_id'], 'vehicle_id' => $vehicle['vehicle_id']]);
    }

    public function test_guest_qr_limit_is_account_wide_and_denial_does_not_consume_the_second_pass(): void
    {
        $this->actingAsStaff();
        $guest = $this->user('other');
        $car = $this->visitorVehicle();
        $bike = $this->visitorVehicle('motorcycle');
        DB::table('visitors')->where('visitor_id', $car['visitor_id'])->update(['user_id' => $guest->user_id]);
        DB::table('vehicles')->where('vehicle_id', $bike['vehicle_id'])->update(['owner_visitor_id' => $car['visitor_id']]);
        $bike['visitor_id'] = $car['visitor_id'];
        $carPass = $this->passFor($car);
        $bikePass = $this->passFor($bike);
        $this->assertSame('granted', $this->gate('entry', ['qr_pass_code' => $carPass])['status']);
        $this->assertSame('denied', $this->gate('entry', ['qr_pass_code' => $bikePass])['status']);
        $this->assertSame(0, (int) DB::table('qr_passes')->where('pass_code', $bikePass)->value('use_count'));
        $this->assertNull($this->activeTransaction($bike['vehicle_id']));
        $this->assertSame('completed', $this->gate('exit', ['qr_pass_code' => $carPass])['status']);
        $this->assertSame('granted', $this->gate('entry', ['qr_pass_code' => $bikePass])['status']);
    }

    public function test_staff_plate_override_does_not_bypass_single_vehicle_rule_for_walk_in_visitor(): void
    {
        $this->actingAsStaff();
        $car = $this->visitorVehicle();
        $bike = $this->visitorVehicle('motorcycle');
        DB::table('vehicles')->where('vehicle_id', $bike['vehicle_id'])->update(['owner_visitor_id' => $car['visitor_id']]);
        $bike['visitor_id'] = $car['visitor_id'];
        $this->passFor($car);
        $this->passFor($bike);
        $this->assertSame('granted', $this->gate('entry', ['plate_number' => $car['plate']])['status']);
        $this->assertSame('denied', $this->gate('entry', ['plate_number' => $bike['plate']])['status']);
        $this->assertSame('completed', $this->gate('exit', ['plate_number' => $car['plate']])['status']);
        $this->assertSame('granted', $this->gate('entry', ['plate_number' => $bike['plate']])['status']);
    }
}
