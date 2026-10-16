<?php

namespace Tests\Feature\Parking;

use App\Services\Parking\ParkingLayoutUpdater;
use App\Support\ParkingMapLayout;
use Illuminate\Support\Facades\DB;

class ParkingMeasurementLayoutTest extends ParkingTestCase
{
    public function test_restored_motorcycle_bank_stays_inside_the_site_and_retains_the_pathway(): void
    {
        $geometry = ParkingMapLayout::geometry();
        $this->assertSame('b43539d786ebd9b613277b05432fda7fe11e8d36d3783d25876ed9302160f586', hash('sha256', json_encode(array_intersect_key($geometry, array_flip(['road', 'gate', 'lanes', 'connector'])))));
        $positions = ParkingMapLayout::slotPositions();
        foreach (range(1, 84) as $number) {
            $code = sprintf('M-%02d', $number);
            $this->assertArrayHasKey($code, $positions);
            $this->assertSame($number, $positions[$code]['slot_number']);
            $this->assertSame(6.5, $positions[$code]['w']);
            $this->assertSame(14.0, $positions[$code]['h']);
        }
        $polygon = $geometry['areas'][0]['polygon'];
        foreach (range(23, 42) as $number) {
            $bay = $positions[sprintf('M-%02d', $number)];
            $radians = deg2rad($bay['rotation']);
            foreach ([-1, 1] as $along) {
                foreach ([-1, 1] as $across) {
                    $x = $bay['x'] + $along * $bay['w'] / 2 * cos($radians) - $across * $bay['h'] / 2 * sin($radians);
                    $y = $bay['y'] + $along * $bay['w'] / 2 * sin($radians) + $across * $bay['h'] / 2 * cos($radians);
                    foreach ($polygon as $index => [$ax, $ay]) {
                        [$bx, $by] = $polygon[($index + 1) % count($polygon)];
                        $this->assertGreaterThanOrEqual(0, ($bx - $ax) * ($y - $ay) - ($by - $ay) * ($x - $ax), "M-{$number} must stay inside the motorcycle area");
                    }
                }
            }
        }
        $this->assertSame('proposed', $positions['M-23']['placement_basis']);
        $this->assertSame('proposed', $positions['M-42']['placement_basis']);
        $this->assertCount(20, $geometry['proposed_slot_codes']);
        $this->assertSame(['160.00 m', '22.67 m', '20.00 m', '40.00 m', '60.00 m', '80.00 m', '100.00 m', '120.00 m', '200.00 m'], array_column($geometry['dimensions'], 'text'));
        $this->assertFalse($geometry['measurement_basis']['survey_verified']);
        $this->assertSame('schematic', $geometry['measurement_basis']['scale']);
    }

    public function test_coordinate_refresh_is_idempotent_and_preserves_sessions_status_capacity_and_gate_state(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $before = $this->activeTransaction($car['vehicle_id']);
        DB::table('parking_slots')->where('slot_code', 'M-23')->update(['slot_status' => 'maintenance', 'pos_x' => null, 'pos_y' => null]);
        DB::table('access_gates')->update(['gate_status' => 'offline']);
        $this->assertGreaterThan(0, app(ParkingLayoutUpdater::class)->apply());
        $this->assertSame(0, app(ParkingLayoutUpdater::class)->apply());
        $this->assertEquals($before, $this->activeTransaction($car['vehicle_id']));
        $this->assertSame(139, DB::table('parking_slots')->count());
        $this->assertSame('maintenance', DB::table('parking_slots')->where('slot_code', 'M-23')->value('slot_status'));
        $this->assertSame('offline', DB::table('access_gates')->value('gate_status'));
        $map = $this->getJson('/api/parking/map')->assertOk()->json();
        $restored = collect($map['slots'])->firstWhere('slot_code', 'M-23');
        $this->assertTrue($restored['placed']);
        $this->assertSame('maintenance', $restored['status']);
        $this->assertSame(84, collect($map['slots'])->where('area_code', 'MC')->where('placed', true)->count());
        $this->assertSame($entry['slot_id'], $this->activeTransaction($car['vehicle_id'])->slot_id);
    }
}
