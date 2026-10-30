<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParkingStationTest extends ParkingTestCase
{
    private function stationFixture(): array
    {
        $this->actingAsStaff();
        $readerId = DB::table('rfid_readers')->insertGetId([
            'reader_code' => 'STATION-'.Str::upper(Str::random(8)),
            'gate_id' => DB::table('access_gates')->where('gate_code', 'MAIN-GATE')->value('gate_id'),
            'api_key_hash' => bcrypt('station-test-key'), 'reader_direction' => 'both', 'reader_status' => 'online',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $reader = DB::table('rfid_readers')->where('reader_id', $readerId)->first();
        $this->withHeaders(['X-Reader-Code' => $reader->reader_code, 'X-Reader-Key' => 'station-test-key']);
        // Determine the allocator's real first slot, without hardcoded test slot codes.
        $probe = $this->campusVehicle();
        $first = $this->gate('entry', ['rfid_tag_uid' => $probe['tag']]);
        $this->gate('exit', ['rfid_tag_uid' => $probe['tag']]);
        $this->postJson('/api/parking/slot-stations', ['reader_id' => $readerId, 'slot_id' => $first['slot_id']])->assertOk();
        $session = (string) Str::uuid();
        $cursor = $this->postJson('/api/devices/parking-station/start', ['session_id' => $session])->assertOk()->json('cursor');
        return ['reader' => $reader, 'slot_id' => $first['slot_id'], 'session' => $session, 'cursor' => $cursor];
    }

    private function reading(array $fixture, int $sequence, string $state, ?float $distance = null)
    {
        return $this->postJson('/api/devices/parking-station/observe', [
            'session_id' => $fixture['session'], 'sequence' => $sequence, 'state' => $state,
            'distance_cm' => $distance, 'oled_ready' => true,
        ]);
    }

    public function test_committed_entry_reaches_oled_once_and_duplicates_do_not_repeat_it(): void
    {
        $f = $this->stationFixture();
        $this->reading($f, 1, 'available', 30)->assertOk();
        $car = $this->campusVehicle();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->assertSame($f['slot_id'], $entry['slot_id']);
        $poll = $this->postJson('/api/devices/parking-station/poll', ['session_id' => $f['session'], 'after_event_id' => $f['cursor']])->assertOk();
        $poll->assertJsonPath('assignment.plate_number', $car['plate'])->assertJsonPath('assignment.slot_code', $entry['slot_code']);
        $this->assertNotEmpty($poll->json('assignment.zone_name'));
        $this->assertNotEmpty($poll->json('assignment.area_name'));
        $this->assertSame('already_inside', $this->gate('entry', ['rfid_tag_uid' => $car['tag']])['status']);
        $this->postJson('/api/devices/parking-station/poll', ['session_id' => $f['session'], 'after_event_id' => $poll->json('cursor')])->assertOk()->assertJsonPath('assignment', null);
    }

    public function test_occupied_after_entry_alerts_staff_without_claiming_vehicle_identity(): void
    {
        $f = $this->stationFixture();
        $this->reading($f, 1, 'available', 30);
        $car = $this->campusVehicle();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->reading($f, 2, 'available', 30);
        $before = DB::table('notifications')->where('notification_type', 'parking_slot_sensor')->count();
        $this->reading($f, 3, 'occupied', 10)->assertOk()->assertJsonPath('arrival_detected', true);
        $txn = DB::table('parking_transactions')->where('transaction_id', $entry['transaction_id'])->first();
        $this->assertNotNull($txn->slot_sensor_detected_at);
        $this->assertNull($txn->slot_confirmed_at);
        $this->assertNull($txn->slot_confirmed_by);
        $after = DB::table('notifications')->where('notification_type', 'parking_slot_sensor')->count();
        $this->assertGreaterThan($before, $after);
        $this->reading($f, 4, 'occupied', 10)->assertOk()->assertJsonPath('arrival_detected', false);
        $this->assertSame($after, DB::table('notifications')->where('notification_type', 'parking_slot_sensor')->count());
        $this->getJson('/api/parking/slot-stations')->assertOk()->assertJsonPath('data.0.arrival_detected', true);
    }

    public function test_startup_occupied_is_not_attributed_to_the_assigned_vehicle(): void
    {
        $f = $this->stationFixture();
        $this->reading($f, 1, 'available', 30);
        $car = $this->campusVehicle();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $f['session'] = (string) Str::uuid();
        $this->postJson('/api/devices/parking-station/start', ['session_id' => $f['session']])->assertOk();
        $this->reading($f, 1, 'occupied', 10)->assertOk()->assertJsonPath('arrival_detected', false);
        $this->assertNull(DB::table('parking_transactions')->where('transaction_id', $entry['transaction_id'])->value('slot_sensor_detected_at'));
    }

    public function test_sensor_never_closes_a_stay_or_changes_its_slot(): void
    {
        $f = $this->stationFixture();$this->reading($f, 1, 'available', 30);
        $car = $this->campusVehicle();$entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->reading($f, 2, 'occupied', 10);$this->reading($f, 3, 'available', 30);
        $this->assertDatabaseHas('parking_transactions', ['transaction_id' => $entry['transaction_id'], 'transaction_status' => 'active', 'slot_id' => $entry['slot_id']]);
        $this->reading($f, 4, 'occupied', 40)->assertUnprocessable();
    }

    public function test_offline_or_occupied_unassigned_sensor_slot_is_not_allocated(): void
    {
        $f = $this->stationFixture();
        $car = $this->campusVehicle();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->assertNotSame($f['slot_id'], $entry['slot_id']);
        $this->reading($f, 1, 'occupied', 10);
        $car2 = $this->campusVehicle();
        $entry2 = $this->gate('entry', ['rfid_tag_uid' => $car2['tag']]);
        $this->assertNotSame($f['slot_id'], $entry2['slot_id']);
        DB::table('parking_slot_stations')->update(['last_seen_at' => now()->subSeconds(20)]);
        $this->getJson('/api/parking/slot-stations')->assertOk()->assertJsonPath('data.0.display_state', 'offline')->assertJsonPath('data.0.arrival_detected', false);
    }

    public function test_replayed_sequences_and_old_sessions_do_not_refresh_state(): void
    {
        $f = $this->stationFixture();$this->reading($f, 2, 'available', 30)->assertOk();
        $this->reading($f, 1, 'occupied', 10)->assertOk()->assertJsonPath('duplicate', true);
        $this->assertDatabaseHas('parking_slot_stations', ['slot_id' => $f['slot_id'], 'sensor_state' => 'available']);
        $new = (string) Str::uuid();
        $this->postJson('/api/devices/parking-station/start', ['session_id' => $new])->assertOk();
        $this->reading($f, 3, 'occupied', 10)->assertStatus(409);
    }

    public function test_denied_or_completed_events_do_not_show_a_new_parking_assignment(): void
    {
        $f = $this->stationFixture();$this->reading($f, 1, 'available', 30);
        $car = $this->campusVehicle('sedan', 'verified', 'suspended');
        $this->assertSame('denied', $this->gate('entry', ['rfid_tag_uid' => $car['tag']])['status']);
        $this->postJson('/api/devices/parking-station/poll', ['session_id' => $f['session'], 'after_event_id' => $f['cursor']])
            ->assertOk()->assertJsonPath('assignment', null)->assertJsonPath('clear_display', true);
    }

    public function test_permissions_and_credentials_are_enforced(): void
    {
        $f = $this->stationFixture();
        $this->withHeaders(['X-Reader-Key' => 'wrong']);
        $this->postJson('/api/devices/parking-station/start', ['session_id' => (string) Str::uuid()])->assertUnauthorized();
        $this->actingAsAdmin();
        $this->postJson('/api/parking/slot-stations', ['reader_id' => $f['reader']->reader_id, 'slot_id' => $f['slot_id']])->assertForbidden();
        $this->getJson('/api/parking/slot-stations')->assertOk()->assertDontSee('api_key_hash')->assertDontSee('station-test-key');
        \Laravel\Sanctum\Sanctum::actingAs($this->user());
        $this->getJson('/api/parking/slot-stations')->assertForbidden();
    }
    public function test_simulator_and_old_entries_are_not_replayed_on_the_oled(): void
    {
        $f = $this->stationFixture();$this->reading($f, 1, 'available', 30);
        $car = $this->campusVehicle();$entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $eventId = DB::table('parking_transactions')->where('transaction_id', $entry['transaction_id'])->value('entry_event_id');
        DB::table('access_events')->where('event_id', $eventId)->update(['raw_payload' => json_encode(['source' => 'simulator'])]);
        $this->postJson('/api/devices/parking-station/poll', ['session_id' => $f['session'], 'after_event_id' => $f['cursor']])->assertOk()->assertJsonPath('assignment', null);
        DB::table('access_events')->where('event_id', $eventId)->update(['raw_payload' => '{}', 'event_datetime' => now()->subMinutes(2)]);
        $this->postJson('/api/devices/parking-station/poll', ['session_id' => $f['session'], 'after_event_id' => $f['cursor']])->assertOk()->assertJsonPath('assignment', null);
    }

    public function test_physical_sensor_binding_cannot_be_changed_by_observation_payload(): void
    {
        $f = $this->stationFixture();
        $other = DB::table('parking_slots')->where('slot_id', '!=', $f['slot_id'])->value('slot_id');
        $this->postJson('/api/devices/parking-station/observe', ['session_id' => $f['session'], 'sequence' => 1,
            'state' => 'occupied', 'distance_cm' => 10, 'oled_ready' => true, 'slot_id' => $other])->assertOk();
        $this->assertDatabaseHas('parking_slot_stations', ['reader_id' => $f['reader']->reader_id, 'slot_id' => $f['slot_id'], 'sensor_state' => 'occupied']);
    }

    public function test_gate_scope_restricts_sensor_binding_and_display_events(): void
    {
        $f = $this->stationFixture();
        $carArea = DB::table('parking_areas')->where('area_code', 'CAR')->value('parking_area_id');
        DB::table('access_gates')->where('gate_id', $f['reader']->gate_id)->update(['parking_area_id' => $carArea]);
        $motorcycleSlot = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->where('z.parking_area_id', '!=', $carArea)->value('s.slot_id');
        $this->postJson('/api/parking/slot-stations', ['reader_id' => $f['reader']->reader_id, 'slot_id' => $motorcycleSlot])->assertUnprocessable();
        $car = $this->campusVehicle();$entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $otherGate = DB::table('access_gates')->insertGetId(['parking_area_id' => $carArea, 'gate_code' => 'OTHER-'.Str::random(6), 'gate_name' => 'Other gate', 'gate_type' => 'both', 'gate_status' => 'online']);
        DB::table('access_events')->where('event_id', DB::table('parking_transactions')->where('transaction_id', $entry['transaction_id'])->value('entry_event_id'))->update(['gate_id' => $otherGate]);
        $this->postJson('/api/devices/parking-station/poll', ['session_id' => $f['session'], 'after_event_id' => $f['cursor']])->assertOk()->assertJsonPath('assignment', null);
    }

    public function test_servo_less_station_does_not_queue_an_automatic_open(): void
    {
        if (!class_exists(\App\Services\Parking\BarrierReceipt::class)) $this->markTestSkipped('Optional automatic barrier integration is not installed.');
        $f = $this->stationFixture();$this->reading($f, 1, 'available', 30);
        $car = $this->campusVehicle();$entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $result = \App\Services\Parking\BarrierReceipt::issue($entry, 'in', null);
        $this->assertSame('unavailable', $result['barrier_status']);
        $this->assertNull($result['barrier_command_id']);
    }

    public function test_clear_baseline_before_scan_can_detect_arrival_and_recovery_does_not_repeat_it(): void
    {
        $f = $this->stationFixture();$this->reading($f, 1, 'available', 30);
        $this->travel(2)->seconds();
        $car = $this->campusVehicle();$this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->reading($f, 2, 'occupied', 10)->assertOk()->assertJsonPath('arrival_detected', true);
        $before = DB::table('notifications')->where('notification_type', 'parking_slot_sensor')->count();
        $this->reading($f, 3, 'unknown');
        $this->reading($f, 4, 'occupied', 10)->assertOk()->assertJsonPath('arrival_detected', false);
        $this->assertSame($before, DB::table('notifications')->where('notification_type', 'parking_slot_sensor')->count());
        $this->getJson('/api/parking/slot-stations')->assertOk()->assertJsonPath('data.0.arrival_detected', true);
    }

}
