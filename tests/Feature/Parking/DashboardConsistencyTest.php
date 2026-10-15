<?php

namespace Tests\Feature\Parking;

use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/** Phase B: Dashboard, Entry & Exit Records and Occupancy Monitor use the same records. */
class DashboardConsistencyTest extends ParkingTestCase
{
    public function test_entries_exits_and_vehicles_inside_reconcile_across_screens(): void
    {
        // One car entered yesterday (Manila) and is still inside.
        $this->travelTo(CarbonImmutable::parse('2026-11-19 16:00', CampusTime::zone()));
        $this->actingAsStaff();
        $overnight = $this->campusVehicle();
        $this->gate('entry', ['rfid_tag_uid' => $overnight['tag']]);

        // Today: 3 cars and 1 motorcycle enter, 1 car exits, 1 attempt is denied.
        $this->travelTo(CarbonImmutable::parse('2026-11-20 08:00', CampusTime::zone()));
        $this->actingAsStaff();
        $cars = [$this->campusVehicle(), $this->campusVehicle(), $this->campusVehicle()];
        $bike = $this->campusVehicle('motorcycle');
        foreach ([...$cars, $bike] as $v) {
            $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => $v['tag']])['status']);
        }
        $this->gate('exit', ['rfid_tag_uid' => $cars[0]['tag']]);
        $this->gate('entry', ['rfid_tag_uid' => 'NO-SUCH-TAG']);

        $dash = $this->getJson('/api/parking/dashboard')->assertOk()->json();
        $this->assertSame(4, $dash['today']['entries']);
        $this->assertSame(1, $dash['today']['exits']);
        $this->assertSame(1, $dash['today']['denied']);
        $this->assertSame(4, $dash['today']['by_method']['rfid']);
        $this->assertSame(4, $dash['today']['by_category']['student']);

        // Inside = 4 (yesterday's car + 2 cars + 1 motorcycle), not today's entries.
        $inside = $dash['inside'];
        $this->assertSame(4, $inside['total']);
        $this->assertSame(3, $inside['cars']);
        $this->assertSame(1, $inside['motorcycles']);
        $this->assertSame(4, $inside['distinct_slots']);
        $this->assertTrue($inside['consistent']);

        foreach (['car', 'motorcycle'] as $area) {
            $a = $dash['occupancy'][$area];
            $this->assertSame($a['capacity'], $a['available'] + $a['occupied'] + $a['maintenance']);
        }
        $this->assertSame(3, $dash['occupancy']['car']['occupied']);
        $this->assertSame(1, $dash['occupancy']['motorcycle']['occupied']);
        $this->assertSame(55, $dash['occupancy']['car']['capacity']);
        $this->assertSame(84, $dash['occupancy']['motorcycle']['capacity']);

        // Occupancy Monitor and Parking Layout report the same figures.
        $overview = $this->getJson('/api/parking/overview')->json();
        $map = $this->getJson('/api/parking/map')->json();
        $this->assertSame($inside, $overview['inside']);
        $this->assertSame($inside, $map['inside']);
        $this->assertSame($dash['occupancy']['overall']['occupied'], $overview['overall']['occupied']);
        $this->assertSame(4, collect($map['slots'])->where('status', 'occupied')->count());

        // Entry & Exit Records: the active transactions are the vehicles inside.
        $active = $this->getJson('/api/parking/transactions?status=active')->json('data');
        $this->assertCount(4, $active);
        $this->assertSame(collect($active)->pluck('slot_code')->sort()->values()->all(),
            collect($map['slots'])->where('status', 'occupied')->pluck('slot_code')->sort()->values()->all());
        $this->assertCount(4, $this->getJson('/api/parking/transactions?date=today&category=student&method=rfid')->json('data'));

        // Recent activity shows the denied attempt and the exit.
        $kinds = collect($dash['recent_activity'])->map(fn ($a) => $a['result'] . ':' . ($a['direction'] ?? ''));
        $this->assertTrue($kinds->contains('denied:in'));
        $this->assertTrue($kinds->contains('granted:out'));
    }

    public function test_faculty_and_employee_rfid_entries_are_counted_by_category(): void
    {
        $this->actingAsStaff();
        $faculty = $this->campusVehicle('suv', 'verified', 'approved', 'faculty');
        $employee = $this->campusVehicle('motorcycle', 'verified', 'approved', 'employee');

        $f = $this->gate('entry', ['rfid_tag_uid' => $faculty['tag']]);
        $e = $this->gate('entry', ['rfid_tag_uid' => $employee['tag']]);
        $this->assertSame('granted', $f['status']);
        $this->assertStringStartsWith('C-', $f['slot_code']);
        $this->assertStringStartsWith('M-', $e['slot_code']);

        $dash = $this->getJson('/api/parking/dashboard')->json();
        $this->assertSame(1, $dash['today']['by_category']['faculty']);
        $this->assertSame(1, $dash['today']['by_category']['employee']);
        $this->assertSame(['faculty'], collect($this->getJson('/api/parking/transactions?category=faculty')->json('data'))->pluck('user_category')->all());
    }

    public function test_denied_attempts_are_never_transactions(): void
    {
        $this->actingAsStaff();
        $suspended = $this->campusVehicle('sedan', 'verified', 'suspended');
        $before = DB::table('parking_transactions')->count();

        $this->gate('entry', ['rfid_tag_uid' => $suspended['tag']]);

        $this->assertSame($before, DB::table('parking_transactions')->count());
        $this->assertSame(0, $this->getJson('/api/parking/dashboard')->json('inside.total'));
        $this->assertNotEmpty($this->getJson('/api/access-events?result=denied&date=today')->json('data'));
    }

    public function test_sync_revision_changes_on_entry_and_is_scoped_for_guests(): void
    {
        $staff = $this->actingAsStaff();
        $before = $this->getJson('/api/sync/revision')->assertOk()->json('topics');

        $car = $this->campusVehicle();
        $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);

        $after = $this->getJson('/api/sync/revision')->json('topics');
        $this->assertNotSame($before['parking'], $after['parking']);
        $this->assertSame($before['visitors'], $after['visitors']);

        // A guest's markers cover only their own records: staff activity on
        // another vehicle does not change them.
        $this->postJson('/api/guest/register', [
            'full_name' => 'Sync Guest', 'email' => 'sync.guest@example.com', 'contact_number' => '09170000009',
            'password' => 'SyncGuest2026', 'password_confirmation' => 'SyncGuest2026', 'agree' => true,
        ])->assertCreated();
        Sanctum::actingAs(\App\Models\User::where('email', 'sync.guest@example.com')->first());
        $guestBefore = $this->getJson('/api/sync/revision')->json('topics');
        $this->assertArrayNotHasKey('violations', $guestBefore);

        Sanctum::actingAs($staff);
        $this->gate('exit', ['rfid_tag_uid' => $car['tag']]);

        Sanctum::actingAs(\App\Models\User::where('email', 'sync.guest@example.com')->first());
        $this->assertSame($guestBefore, $this->getJson('/api/sync/revision')->json('topics'));
    }
}
