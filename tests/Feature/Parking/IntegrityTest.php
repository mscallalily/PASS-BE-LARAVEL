<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;

/** Phase A: slot integrity, reassignment safety, ended sessions, and timestamps. */
class IntegrityTest extends ParkingTestCase
{
    private function slotId(string $code): int
    {
        return (int) DB::table('parking_slots')->where('slot_code', $code)->value('slot_id');
    }

    /** Park a car, then report it at another slot; returns [car, violation id, entry result]. */
    private function wrongSlot(string $observed): array
    {
        $car = $this->campusVehicle();
        $in = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $txn = $this->activeTransaction($car['vehicle_id']);
        $report = $this->postJson("/api/parking/transactions/{$txn->transaction_id}/observed-slot", ['slot_code' => $observed])->assertOk()->json();
        $this->assertSame('incident', $report['status']);

        return [$car, $report['violation_id'], $in];
    }

    public function test_reassignment_to_a_free_slot_moves_the_assignment(): void
    {
        $this->actingAsStaff();
        [$car, $violationId] = $this->wrongSlot('C-50');

        $this->patchJson("/api/violations/{$violationId}", ['action' => 'resolve', 'resolution' => 'reassigned'])->assertOk();

        $this->assertSame($this->slotId('C-50'), (int) $this->activeTransaction($car['vehicle_id'])->slot_id);
        $this->assertSame('resolved', DB::table('parking_violations')->where('violation_id', $violationId)->value('violation_status'));
    }

    public function test_reassignment_to_an_occupied_maintenance_or_wrong_type_slot_is_a_clear_conflict(): void
    {
        $this->actingAsStaff();
        $other = $this->campusVehicle();
        $otherSlot = $this->gate('entry', ['rfid_tag_uid' => $other['tag']])['slot_code'];

        [, $occupied] = $this->wrongSlot($otherSlot);
        $this->patchJson("/api/violations/{$occupied}", ['action' => 'resolve', 'resolution' => 'reassigned'])
            ->assertStatus(409)->assertJsonPath('message', "Slot {$otherSlot} is assigned to another vehicle.");

        DB::table('parking_slots')->where('slot_code', 'C-49')->update(['slot_status' => 'maintenance']);
        [, $maintenance] = $this->wrongSlot('C-49');
        $this->patchJson("/api/violations/{$maintenance}", ['action' => 'resolve', 'resolution' => 'reassigned'])->assertStatus(409);

        [, $wrongArea] = $this->wrongSlot('M-30');
        $this->patchJson("/api/violations/{$wrongArea}", ['action' => 'resolve', 'resolution' => 'reassigned'])->assertStatus(409);

        // Nothing changed: still exactly one active stay per slot.
        $this->assertTrue(DB::table('parking_transactions')->where('transaction_status', 'active')->whereNotNull('slot_id')
            ->groupBy('slot_id')->havingRaw('COUNT(*) > 1')->pluck('slot_id')->isEmpty());
    }

    public function test_incident_of_an_exited_vehicle_stays_open_until_resolved_without_a_slot_change(): void
    {
        $this->actingAsStaff();
        [$car, $violationId] = $this->wrongSlot('C-48');
        $this->gate('exit', ['rfid_tag_uid' => $car['tag']]);

        $row = collect($this->getJson('/api/violations?status=open')->json('data'))->firstWhere('violation_id', $violationId);
        $this->assertTrue($row['session_ended']);

        // The map no longer marks a slot for it.
        $map = collect($this->getJson('/api/parking/map')->json('slots'));
        $this->assertNull($map->firstWhere('slot_code', 'C-48')['incident']);

        $this->patchJson("/api/violations/{$violationId}", ['action' => 'resolve', 'resolution' => 'reassigned'])->assertStatus(409);
        $this->patchJson("/api/violations/{$violationId}", ['action' => 'resolve', 'remarks' => 'Vehicle left'])->assertOk();
        $this->assertSame('resolved', DB::table('parking_violations')->where('violation_id', $violationId)->value('violation_status'));
    }

    public function test_maintenance_slots_are_never_assigned_and_a_full_area_denies_entry(): void
    {
        $this->actingAsStaff();
        DB::table('parking_slots')->where('slot_code', 'like', 'C-%')->where('slot_code', '<>', 'C-55')->update(['slot_status' => 'maintenance']);

        $first = $this->campusVehicle();
        $this->assertSame('C-55', $this->gate('entry', ['rfid_tag_uid' => $first['tag']])['slot_code']);

        $second = $this->campusVehicle();
        $r = $this->gate('entry', ['rfid_tag_uid' => $second['tag']]);
        $this->assertSame('denied', $r['status']);
        $this->assertSame('Car Parking Area is full.', $r['reason']);
        $this->assertNull($this->activeTransaction($second['vehicle_id']));

        // A motorcycle still gets a motorcycle slot.
        $bike = $this->campusVehicle('motorcycle');
        $this->assertStringStartsWith('M-', $this->gate('entry', ['rfid_tag_uid' => $bike['tag']])['slot_code']);
    }

    public function test_registration_writes_utc_times_not_database_default_manila_times(): void
    {
        $this->postJson('/api/register', [])->assertUnprocessable(); // endpoint exists; full form below
        $before = now()->subMinute();

        $this->postJson('/api/guest/register', [
            'full_name' => 'Time Check', 'email' => 'timecheck@example.com', 'contact_number' => '09170000001',
            'password' => 'Timecheck2026', 'password_confirmation' => 'Timecheck2026', 'agree' => true,
        ])->assertCreated();

        $userId = DB::table('users')->where('email', 'timecheck@example.com')->value('user_id');
        $assigned = DB::table('user_roles')->where('user_id', $userId)->value('assigned_at');
        $this->assertTrue($assigned >= $before->toDateTimeString() && $assigned <= now()->addMinute()->toDateTimeString(), "assigned_at {$assigned} is not UTC now");
    }
}
