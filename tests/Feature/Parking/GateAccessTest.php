<?php

namespace Tests\Feature\Parking;

use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GateAccessTest extends ParkingTestCase
{
    public function test_rfid_entry_creates_one_transaction_and_slot_and_exit_releases_it(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $eventsBefore = DB::table('access_events')->count();

        $in = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);

        $this->assertSame('granted', $in['status']);
        $this->assertStringStartsWith('C-', $in['slot_code']);
        $txn = $this->activeTransaction($car['vehicle_id']);
        $this->assertSame('rfid', $txn->access_method);
        $this->assertSame($car['driver_id'], (int) $txn->driver_id);
        $this->assertNotNull($txn->entry_event_id);
        $this->assertSame($eventsBefore + 1, DB::table('access_events')->count());

        // A repeated scan neither creates a stay nor assigns another slot.
        $this->assertSame('already_inside', $this->gate('entry', ['rfid_tag_uid' => $car['tag']])['status']);
        $this->assertSame(1, DB::table('parking_transactions')->where('vehicle_id', $car['vehicle_id'])->count());

        $out = $this->gate('exit', ['rfid_tag_uid' => $car['tag']]);

        $this->assertSame('completed', $out['status']);
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
        $this->assertFalse(DB::table('parking_transactions')->where('slot_id', $in['slot_id'])->where('transaction_status', 'active')->exists());
        $this->assertSame('not_inside', $this->gate('exit', ['rfid_tag_uid' => $car['tag']])['status']);
    }

    public function test_unknown_rfid_tag_is_denied_and_recorded_without_a_transaction(): void
    {
        $this->actingAsStaff();
        $txnsBefore = DB::table('parking_transactions')->count();

        $r = $this->gate('entry', ['rfid_tag_uid' => 'NO-SUCH-TAG']);

        $this->assertSame('denied', $r['status']);
        $this->assertSame('Unknown RFID tag.', $r['reason']);
        $this->assertSame($txnsBefore, DB::table('parking_transactions')->count());
        $this->assertDatabaseHas('access_events', ['event_id' => $r['access_event_id'], 'auth_result' => 'denied', 'access_method' => 'rfid']);
    }

    public function test_motorcycle_is_assigned_a_motorcycle_slot(): void
    {
        $this->actingAsStaff();
        $bike = $this->campusVehicle('motorcycle');

        $this->assertStringStartsWith('M-', $this->gate('entry', ['rfid_tag_uid' => $bike['tag']])['slot_code']);
    }

    public function test_unverified_license_blocks_rfid_and_plate_number_entry(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle('sedan', 'pending_review');

        $rfid = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $plate = $this->gate('entry', ['plate_number' => $car['plate']]);

        $this->assertSame('denied', $rfid['status']);
        $this->assertStringContainsString('license', $rfid['reason']);
        $this->assertSame('denied', $plate['status']);
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
    }

    public function test_suspended_authorization_is_denied(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle('sedan', 'verified', 'suspended');

        $r = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);

        $this->assertSame('denied', $r['status']);
        $this->assertSame('Vehicle authorization is suspended.', $r['reason']);
    }

    public function test_visitor_vehicle_cannot_enter_with_rfid(): void
    {
        $staff = $this->actingAsStaff();
        $visitor = $this->visitorVehicle();
        $tag = $this->rfidTag($staff->user_id, $visitor['vehicle_id']);

        $r = $this->gate('entry', ['rfid_tag_uid' => $tag]);

        $this->assertSame('denied', $r['status']);
        $this->assertStringContainsString('Visitor QR', $r['reason']);
    }

    public function test_campus_vehicle_cannot_enter_with_a_qr_pass(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $code = $this->rawPass(['user_id' => $car['user']->user_id, 'vehicle_id' => $car['vehicle_id']]);

        $r = $this->gate('entry', ['qr_pass_code' => $code]);

        $this->assertSame('denied', $r['status']);
        $this->assertStringContainsString('RFID', $r['reason']);
    }

    public function test_plate_number_entry_is_recorded_as_a_staff_override(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();

        $r = $this->gate('entry', ['plate_number' => strtolower($car['plate'])]);

        $this->assertSame('granted', $r['status']);
        $this->assertTrue($r['staff_override']);
        $txn = $this->activeTransaction($car['vehicle_id']);
        $this->assertSame('manual', $txn->access_method);
        $payload = json_decode(DB::table('access_events')->where('event_id', $txn->entry_event_id)->value('raw_payload'), true);
        $this->assertTrue($payload['staff_override']);
        $this->assertSame('staff_gate', $payload['source']);
    }

    public function test_dashboard_counts_use_the_campus_day(): void
    {
        // 07:00 in Manila is 23:00 UTC of the previous day.
        $this->travelTo(CarbonImmutable::parse('2026-11-20 07:00', CampusTime::zone()));
        $this->actingAsStaff();
        $car = $this->campusVehicle();

        $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->gate('entry', ['rfid_tag_uid' => 'NO-SUCH-TAG']);

        $dash = $this->getJson('/api/parking/dashboard')->assertOk()->json();

        $this->assertSame('2026-11-20', $dash['campus_date']);
        $this->assertSame(1, $dash['today_entries']);
        $this->assertSame(1, $dash['denied_access']);
    }

    public function test_regular_users_cannot_use_staff_parking_endpoints(): void
    {
        $this->actingAs($this->user('student'), 'sanctum');

        $this->postJson('/api/parking/entry', ['plate_number' => 'ABC 123'])->assertForbidden();
        $this->getJson('/api/parking/dashboard')->assertForbidden();
        $this->getJson('/api/access-events')->assertForbidden();
    }
}
