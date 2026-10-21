<?php

namespace Tests\Feature\Parking;

use App\Support\PlateNumber;
use Illuminate\Support\Facades\DB;

class ParkingFixtureTest extends ParkingTestCase
{
    public function test_fixture_generator_skips_an_existing_archived_separator_alias(): void
    {
        $first = $this->nextTestPlate('TST');
        $number = (int) substr($first, 4) + 1;
        $alias = 'TST-' . $number;
        $user = $this->user();
        $id = DB::table('vehicles')->insertGetId([
            'owner_user_id' => $user->user_id,
            'vehicle_type_id' => DB::table('vehicle_types')->where('type_code', 'sedan')->value('vehicle_type_id'),
            'plate_number' => $alias, 'registration_status' => 'archived', 'deleted_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ], 'vehicle_id');

        $next = $this->nextTestPlate('TST');
        $this->assertNotSame(PlateNumber::key($alias), PlateNumber::key($next));
        $this->assertGreaterThan($number, (int) substr($next, 4));
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $id, 'plate_number' => $alias, 'registration_status' => 'archived']);
    }

    public function test_unstored_payloads_and_campus_and_visitor_fixtures_have_distinct_plates(): void
    {
        $reserved = [];
        foreach (range(1, 20) as $i) $reserved[] = $this->nextTestPlate('NEW');
        foreach (range(1, 4) as $i) {
            $reserved[] = $this->campusVehicle()['plate'];
            $reserved[] = $this->visitorVehicle()['plate'];
        }
        $this->assertCount(count($reserved), array_unique(array_map([PlateNumber::class, 'key'], $reserved)));
        foreach ($reserved as $plate) $this->assertLessThanOrEqual(20, strlen($plate));
    }
}
