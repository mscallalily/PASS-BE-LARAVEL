<?php

namespace Tests\Feature\Parking;

use App\Services\Parking\ParkingLayoutUpdater;
use App\Support\ParkingMapLayout;
use Illuminate\Support\Facades\DB;

class ParkingMeasurementLayoutTest extends ParkingTestCase
{
    public function test_metric_parcels_retain_owner_dimensions_and_proposal_limits(): void
    {
        $g = ParkingMapLayout::geometry(true);
        $unit = $g['measurement_basis']['units_per_metre'];
        $mc = $g['areas'][0]['polygon'];
        $this->assertEqualsWithDelta(30.96, $this->distance($mc[0], $mc[1]) / $unit, 0.00001);
        $this->assertEqualsWithDelta(8.8, $this->distance($mc[1], $mc[2]) / $unit, 0.00001);
        $road = $g['road']['polygon'];
        $this->assertEqualsWithDelta(3.06, $this->distance($road[1], $road[2]) / $unit, 0.00001);
        $this->assertSame([69, 114], $g['gate']['at']);
        $this->assertSame(-21, $g['gate']['angle']);
        $this->assertSame('alternating', $g['road']['traffic']);
        $car = $g['areas'][1]['polygon'];
        foreach ([18.42, 12.04, 2.60, 20.18, 33.37, 3.00, 3.36, 49.26] as $i => $length) {
            $this->assertEqualsWithDelta($length, $this->distance($car[$i], $car[($i + 1) % 8]) / $unit, 0.00001);
        }
        $this->assertCount(116, ParkingMapLayout::slotPositions(true));
        $this->assertSame(['motorcycle_configured' => 84, 'motorcycle_drawn' => 84, 'car_configured' => 55, 'car_drawn' => 32], $g['measurement_basis']['inventory']);
        $this->assertFalse($g['measurement_basis']['survey_verified']);
        $this->assertStringContainsString('not a maximum-capacity', $g['measurement_basis']['car_proposal']);
        $this->assertStringContainsString('walk/push', $g['measurement_basis']['motorcycle_proposal']);
        $this->assertSame([], $g['trays']);
        $this->assertNotContains('160.00 m', array_column($g['dimensions'], 'text'));
    }

    public function test_bays_are_inside_parcels_without_overlapping_other_bays_aisles_or_the_road(): void
    {
        $g = ParkingMapLayout::geometry(true);
        $positions = ParkingMapLayout::slotPositions(true);
        $this->assertCount(84, array_filter($positions, fn ($b) => $b['area'] === 'MC'));
        $this->assertCount(32, array_filter($positions, fn ($b) => $b['area'] === 'CAR'));
        foreach (range(1, 84) as $i) {
            $this->assertArrayHasKey(sprintf('M-%02d', $i), $positions);
        }
        $polygons = [];
        foreach ($positions as $code => $bay) {
            $this->assertSame('proposed', $bay['placement_basis']);
            $this->assertEqualsWithDelta($bay['area'] === 'MC' ? 1 : ($bay['pwd'] ? 4 : ($bay['w'] > $bay['h'] ? 6 : 2.5)), $bay['w'] / 8, 0.00001);
            $this->assertEqualsWithDelta($bay['area'] === 'MC' ? 2 : ($bay['w'] > $bay['h'] ? 2.15 : 5), $bay['h'] / 8, 0.00001);
            $poly = $this->corners($bay);
            $parcel = $g['areas'][$bay['area'] === 'MC' ? 0 : 1]['polygon'];
            // Sample the whole perimeter: concave notches cannot be bridged by a bay.
            foreach ($poly as $i => $a) {
                $b = $poly[($i + 1) % 4];
                foreach (range(0, 20) as $step) {
                    $p = [$a[0] + ($b[0] - $a[0]) * $step / 20, $a[1] + ($b[1] - $a[1]) * $step / 20];
                    $this->assertTrue($this->inside($p, $parcel), "$code must remain inside its parcel");
                }
            }
            foreach (array_merge([$g['road']], $g['circulation']) as $ci => $clearance) {
                $this->assertFalse($this->overlap($poly, $clearance['polygon']), "$code overlaps clearance $ci ".($clearance['kind'] ?? 'road'));
            }
            foreach ($polygons as $otherCode => $other) {
                $this->assertFalse($this->overlap($poly, $other), "$code overlaps $otherCode");
            }
            $polygons[$code] = $poly;
        }
    }

    public function test_complete_slot_diagram_places_all_55_car_codes_once_with_clear_symbols(): void
    {
        $positions = ParkingMapLayout::slotPositions();
        $g = ParkingMapLayout::geometry();
        $this->assertCount(139, $positions);
        $this->assertSame('slot_diagram', $g['measurement_basis']['scale']);
        $this->assertSame(55, $g['measurement_basis']['inventory']['car_drawn']);
        $this->assertSame('metric_proposal', ParkingMapLayout::measuredLayout()['geometry']['measurement_basis']['scale']);
        $polygons = [];
        foreach (range(1, 55) as $number) {
            $code = sprintf('C-%02d', $number);
            $this->assertArrayHasKey($code, $positions);
            $bay = $positions[$code];
            $this->assertSame($number, $bay['slot_number']);
            $this->assertSame('diagram', $bay['placement_basis']);
            $poly = $this->corners($bay);
            foreach ($poly as $p) {
                $this->assertTrue($this->inside($p, $g['areas'][1]['polygon']), "$code symbol outside the car parcel");
            }
            foreach ($g['circulation'] as $clearance) {
                $this->assertFalse($this->overlap($poly, $clearance['polygon']), "$code symbol overlaps a displayed aisle");
            }
            foreach ($polygons as $other) {
                $this->assertFalse($this->overlap($poly, $other), "$code symbol overlaps another symbol");
            }
            $polygons[$code] = $poly;
        }
    }

    public function test_mixed_rows_add_bays_without_blocking_frontages_or_shared_approach(): void
    {
        $g = ParkingMapLayout::geometry(true);
        $bays = array_filter(ParkingMapLayout::bays(true), fn ($bay) => $bay['area'] === 'CAR');
        $counts = array_count_values(array_column($bays, 'row'));
        $this->assertSame(12, $counts['North row']);
        $this->assertSame(11, $counts['Central row']);
        $this->assertSame(8, $counts['West / lower edge']);
        $this->assertSame(1, $counts['North row / accessible']);
        $this->assertSame(29, count(array_filter($g['circulation'], fn ($c) => (float) ($c['width_m'] ?? 0) === 6.0)));
        $this->assertSame(3, count(array_filter($g['circulation'], fn ($c) => $c['kind'] === 'parallel_aisle' && $c['width_m'] === 3.6)));
        $this->assertSame(['C-24', 'C-28', 'C-29'], array_keys(array_filter(ParkingMapLayout::slotPositions(true), fn ($bay) => $bay['area'] === 'CAR' && $bay['w'] > $bay['h'])));
        $this->assertSame(55, $g['measurement_basis']['capacity_review']['reported_car_capacity']);
        $this->assertSame(23, $g['measurement_basis']['capacity_review']['unresolved_car_positions']);
        foreach (array_filter($g['circulation'], fn ($c) => in_array($c['kind'], ['car_aisle', 'parallel_aisle', 'walkway'])) as $clearance) {
            foreach ($clearance['polygon'] as $i => $from) {
                $to = $clearance['polygon'][($i + 1) % 4];
                foreach (range(0, 20) as $step) {
                    $point = [$from[0] + ($to[0] - $from[0]) * $step / 20, $from[1] + ($to[1] - $from[1]) * $step / 20];
                    $this->assertTrue($this->inside($point, $g['areas'][1]['polygon']), 'Frontage must remain inside the car parcel');
                }
            }
        }
        $this->assertCount(3, array_filter($g['circulation'], fn ($c) => $c['kind'] === 'car_approach' && $c['width_m'] === 3.06));
        $this->assertFalse($g['measurement_basis']['vehicle_measurements']['car_mirrors_included']);
        $this->assertSame(2.0447, $g['measurement_basis']['vehicle_measurements']['car_width_m']);
        foreach ($g['lanes'] as $lane) {
            foreach (array_slice($lane['points'], 0, -1) as $i => $from) {
                $to = $lane['points'][$i + 1];
                foreach (range(0, 20) as $step) {
                    $p = [$from[0] + ($to[0] - $from[0]) * $step / 20, $from[1] + ($to[1] - $from[1]) * $step / 20];
                    foreach ($bays as $bay) {
                        $this->assertFalse($this->inside($p, $this->corners($bay)), 'Flow arrows must not cross a car bay');
                    }
                }
            }
        }
        foreach ($bays as $bay) {
            $this->assertFalse($this->overlap($this->corners($bay), $g['connector']['polygon']), 'No bay may block the shared entry/exit connector');
        }
    }

    private function distance(array $a, array $b): float
    {
        return hypot($b[0] - $a[0], $b[1] - $a[1]);
    }

    private function corners(array $b): array
    {
        $r = deg2rad($b['rotation']);

        return array_map(fn ($p) => [$b['x'] + $p[0] * cos($r) - $p[1] * sin($r), $b['y'] + $p[0] * sin($r) + $p[1] * cos($r)], [[-$b['w'] / 2, -$b['h'] / 2], [$b['w'] / 2, -$b['h'] / 2], [$b['w'] / 2, $b['h'] / 2], [-$b['w'] / 2, $b['h'] / 2]]);
    }

    private function inside(array $p, array $poly): bool
    {
        $inside = false;
        foreach ($poly as $i => $a) {
            $b = $poly[($i + 1) % count($poly)];
            $dx = $b[0] - $a[0];
            $dy = $b[1] - $a[1];
            $t = max(0, min(1, (($p[0] - $a[0]) * $dx + ($p[1] - $a[1]) * $dy) / ($dx * $dx + $dy * $dy)));
            if (hypot($p[0] - $a[0] - $t * $dx, $p[1] - $a[1] - $t * $dy) < 0.005) {
                return true;
            }
            if (($a[1] > $p[1]) !== ($b[1] > $p[1]) && $p[0] < ($b[0] - $a[0]) * ($p[1] - $a[1]) / ($b[1] - $a[1]) + $a[0]) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /** Separating-axis test for two convex polygons; touching edges are allowed. */
    private function overlap(array $a, array $b): bool
    {
        foreach ([$a, $b] as $poly) {
            foreach ($poly as $i => $p) {
                $q = $poly[($i + 1) % count($poly)];
                $length = $this->distance($p, $q);
                $axis = [-($q[1] - $p[1]) / $length, ($q[0] - $p[0]) / $length];
                $pa = array_map(fn ($v) => $v[0] * $axis[0] + $v[1] * $axis[1], $a);
                $pb = array_map(fn ($v) => $v[0] * $axis[0] + $v[1] * $axis[1], $b);
                if (min(max($pa), max($pb)) - max(min($pa), min($pb)) <= 0.01) {
                    return false;
                }
            }
        }

        return true;
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
        $this->assertSame(55, collect($map['slots'])->where('area_code', 'CAR')->where('placed', true)->count());
        $this->assertSame(84, collect($map['slots'])->where('area_code', 'MC')->where('placed', true)->count());
        $this->assertSame($entry['slot_id'], $this->activeTransaction($car['vehicle_id'])->slot_id);
    }
}
