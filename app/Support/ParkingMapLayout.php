<?php

namespace App\Support;

/** Proposed metric layout, not a surveyed or approved campus capacity plan. */
class ParkingMapLayout
{
    public const VIEW_BOX = [35, -15, 740, 380];

    /** Integer database-coordinate precision; separate from the metric drawing scale. */
    public const SCALE = 10;

    public const UNITS_PER_METRE = 8;

    /** Main boundary edges numbered 1–6 by the owner. */
    public const CAR_EDGES = [18.42, 12.04, 2.60, 20.18, 33.37, 49.26];

    public const CAR_ORIGIN = [317.91078661903276, -0.590827444327374];

    /** Includes the shared 8.80m motorcycle end and an estimated 4.66m NW return. */
    private const CAR_POINTS = [[49.26, 0.0], [52.98931172464729, 18.03853192641819], [41.32364283951358, 21.017419212373003], [43.101365223700995, 19.12013085138013], [29.29349757934485, 33.83662469726417], [3.704394930091045, 12.418064390699403], [-0.8279401291174331, 4.874992144520814], [-3.593536864708967, 2.966832115570032], [-4.75175454539567e-14, -4.085620730620576e-14]];

    /** Local metres -> the shared drawing coordinate system. */
    private static function point(string $area, float $x, float $y): array
    {
        $angle = deg2rad($area === 'CAR' ? 10 : -21);
        $origin = $area === 'CAR' ? self::CAR_ORIGIN : [69, 114];
        if ($area === 'MC') {
            $y += 1.53; // The motorcycle parcel begins beyond the separate access road.
        }

        return [$origin[0] + self::UNITS_PER_METRE * ($x * cos($angle) - $y * sin($angle)), $origin[1] + self::UNITS_PER_METRE * ($x * sin($angle) + $y * cos($angle))];
    }

    private static function rectangle(string $area, float $x, float $y, float $w, float $h): array
    {
        return array_map(fn ($p) => self::point($area, ...$p), [[$x, $y], [$x + $w, $y], [$x + $w, $y + $h], [$x, $y + $h]]);
    }

    /** Full-size mixed-row candidate; access and boundary bearings remain provisional. */
    private static function carProposal(): array
    {
        return [
            ['x' => 1.980000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 8.600000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 11.100000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 13.600000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 16.100000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 18.600000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 21.100000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 23.600000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 26.100000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 28.600000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 31.100000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 33.600000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 36.100000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 38.600000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 41.100000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 43.600000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 46.100000000, 'y' => 0.000000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 15.200000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 17.700000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 20.200000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 22.700000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 25.200000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 27.700000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 30.200000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 32.700000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 35.200000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 37.700000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 40.200000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 42.700000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 45.200000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 47.700000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 50.200000000, 'y' => 14.500000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 18.000000000, 'y' => 14.500000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row B', 'type' => 'perpendicular'],
            ['x' => 20.500000000, 'y' => 14.500000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row B', 'type' => 'perpendicular'],
            ['x' => 23.000000000, 'y' => 14.500000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row B', 'type' => 'perpendicular'],
            ['x' => 25.500000000, 'y' => 14.500000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row B', 'type' => 'perpendicular'],
            ['x' => 28.000000000, 'y' => 14.500000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row B', 'type' => 'perpendicular'],
            ['x' => 30.500000000, 'y' => 14.500000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row B', 'type' => 'perpendicular'],
            ['x' => 33.000000000, 'y' => 14.500000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row B', 'type' => 'perpendicular'],
            ['x' => 35.500000000, 'y' => 14.500000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'Interior row B', 'type' => 'perpendicular'],
            ['x' => 26.200000000, 'y' => 29.000000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'South row', 'type' => 'perpendicular'],
            ['x' => 28.700000000, 'y' => 29.000000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'South row', 'type' => 'perpendicular'],
            ['x' => 31.200000000, 'y' => 29.000000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'South row', 'type' => 'perpendicular'],
            ['x' => 33.700000000, 'y' => 29.000000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 4.5, 'row' => 'South row', 'type' => 'perpendicular'],
        ];
    }

    private static function carRectangle(array $origin, float $width, float $depth, float $angle = 0): array
    {
        $r = deg2rad($angle);

        return array_map(fn ($p) => self::point('CAR', $origin[0] + $p[0] * cos($r) - $p[1] * sin($r), $origin[1] + $p[0] * sin($r) + $p[1] * cos($r)), [[0, 0], [$width, 0], [$width, $depth], [0, $depth]]);
    }

    /** The 3.06 m shared approach, through the western opening. */
    private static function carApproach(): array
    {
        return [[-2.2107384969132, 3.920912130045423], [1.4, 6.6], [4.4, 8.5], [10.0, 8.5]];
    }

    public static function bays(bool $measured = false): array
    {
        $bays = [];
        $add = function (string $area, int $number, float $x, float $y, float $w, float $h, string $row, bool $pwd = false, float $angle = 0) use (&$bays, $measured) {
            $r = deg2rad($angle);
            [$gx, $gy] = self::point($area, $x + $w / 2 * cos($r) - $h / 2 * sin($r), $y + $w / 2 * sin($r) + $h / 2 * cos($r));
            $bays[] = ['area' => $area, 'row' => $row, 'index' => $number - 1, 'x' => round($gx, 3), 'y' => round($gy, 3), 'w' => $w * self::UNITS_PER_METRE, 'h' => $h * self::UNITS_PER_METRE, 'rotation' => $area === 'CAR' ? 10.0 + $angle : -21.0, 'label' => (string) $number, 'slot_number' => $number, 'kind' => $pwd ? 'pwd' : 'slot', 'pwd' => $pwd, 'placement_basis' => $area === 'CAR' && ! $measured ? 'diagram' : 'proposed', 'note' => 'Proposed position; physical layout and manoeuvring require campus confirmation.'];
        };
        // 28.00 m of bays, 0.40 m end margin and 2.56 m connecting end aisle.
        // Width: 0.20 + 2.00 + 1.20 + 2.00 + 1.20 + 2.00 + 0.20 = 8.80 m.
        foreach ([0.2, 3.4, 6.6] as $row => $y) {
            foreach (range(0, 27) as $column) {
                $add('MC', $row * 28 + $column + 1, 0.4 + $column, $y, 1.0, 2.0, 'Compact motorcycle row '.($row + 1));
            }
        }
        if ($measured) {
            $add('CAR', 42, 4.5, 0, 2.5, 5.0, 'North perimeter');
            $codes = array_merge(range(1, 41), range(43, 55));
            foreach (self::carProposal() as $i => $bay) {
                $add('CAR', $codes[$i], $bay['x'], $bay['y'], $bay['width'], $bay['depth'], $bay['row'], false, $bay['angle']);
            }
        } else {
            // All 55 existing car codes have separate diagram symbols. These are
            // deliberately NOT metre-sized bays; physical fit remains a separate view.
            $add('CAR', 42, 7.5, 0, 2.8, 3.6, 'Diagram north row');
            foreach (range(1, 18) as $i) {
                $add('CAR', $i, 10.5 + ($i - 1) * 2, 0, 1.8, 3.6, 'Diagram north row');
            }
            foreach (range(19, 37) as $i) {
                $add('CAR', $i, 10.5 + ($i - 19) * 2, 6.6, 1.8, 3.6, 'Diagram middle row');
            }
            foreach (array_merge(range(38, 41), range(43, 55)) as $column => $i) {
                $add('CAR', $i, 13 + $column * 2, 13.2, 1.8, 3.6, 'Diagram south row');
            }
        }

        return $bays;
    }

    public static function slotPositions(bool $measured = false): array
    {
        $positions = [];
        foreach (self::bays($measured) as $bay) {
            $positions[sprintf('%s-%02d', $bay['area'] === 'CAR' ? 'C' : 'M', $bay['slot_number'])] = $bay;
        }

        return $positions;
    }

    public static function geometry(bool $measured = false): array
    {
        $mc = self::rectangle('MC', 0, 0, 30.96, 8.8);
        $car = array_map(fn ($p) => self::point('CAR', ...$p), self::CAR_POINTS);
        $clearances = [];
        foreach ([2.2, 5.4] as $y) {
            $clearances[] = ['polygon' => self::rectangle('MC', 0.4, $y, 28, 1.2), 'kind' => 'motorcycle_aisle', 'width_m' => 1.2];
        }
        $clearances[] = ['polygon' => self::rectangle('MC', 28.4, 0, 2.56, 8.8), 'kind' => 'motorcycle_end_aisle', 'width_m' => 2.56];
        if ($measured) {
            $clearances[] = ['polygon' => self::carRectangle([4.5, 5], 2.5, 4.5), 'kind' => 'car_aisle', 'width_m' => 4.5];
            foreach (self::carProposal() as $bay) {
                $angle = deg2rad($bay['angle']);
                $front = [$bay['x'] - $bay['depth'] * sin($angle), $bay['y'] + $bay['depth'] * cos($angle)];
                $clearances[] = ['polygon' => self::carRectangle($front, $bay['width'], $bay['aisle'], $bay['angle']), 'kind' => $bay['type'] === 'parallel' ? 'parallel_aisle' : 'car_aisle', 'width_m' => $bay['aisle']];
            }
            $approach = self::carApproach();
            foreach (array_slice($approach, 0, -1) as $i => $from) {
                $to = $approach[$i + 1];
                $angle = rad2deg(atan2($to[1] - $from[1], $to[0] - $from[0]));
                $r = deg2rad($angle);
                $origin = [$from[0] + 1.53 * sin($r), $from[1] - 1.53 * cos($r)];
                $clearances[] = ['polygon' => self::carRectangle($origin, hypot($to[0] - $from[0], $to[1] - $from[1]), 3.06, $angle), 'kind' => 'car_approach', 'width_m' => 3.06];
            }
        } else {
            foreach ([[7.5, 3.6, 39, 3], [10.5, 10.2, 38, 3], [7.5, 3.6, 3, 6.6]] as $rect) {
                $clearances[] = ['polygon' => self::rectangle('CAR', ...$rect), 'kind' => 'diagram_aisle'];
            }
        }

        return [
            'areas' => [
                ['area' => 'MC', 'polygon' => $mc, 'label' => '84 COMPACT MOTORCYCLE BAYS · PROPOSED', 'label_at' => self::point('MC', 14.2, 9.9), 'label_angle' => -21, 'sign_at' => self::point('MC', 15.48, 10.2)],
                ['area' => 'CAR', 'polygon' => $car, 'label' => $measured ? '45 FULL-SIZE CAR BAYS · PROPOSED' : '55 CAR SLOTS · DIAGRAM', 'label_at' => self::point('CAR', 27, 13.3), 'label_angle' => 10, 'sign_at' => self::point('CAR', 28, -1.4)],
            ],
            'road' => ['polygon' => self::rectangle('MC', 0, -3.06, 30.96, 3.06), 'label' => 'SHARED ACCESS · 3.06 m', 'label_at' => self::point('MC', 15.5, -1.53), 'label_angle' => -21, 'width_m' => 3.06, 'traffic' => 'alternating'],
            'gate' => ['at' => [69, 114], 'angle' => -21, 'label' => 'MAIN ENTRANCE / EXIT', 'sublabel' => 'Shared controlled access', 'label_at' => [95, 27]],
            'lanes' => [
                ['points' => [self::point('MC', 6, -1.53), self::point('MC', 10, -1.53)], 'kind' => 'road_in'],
                ['points' => [self::point('MC', 25, -1.53), self::point('MC', 21, -1.53)], 'kind' => 'road_out'],
                ['points' => [self::point('MC', 29.68, -1.53), self::point('MC', 29.68, 7.8)], 'kind' => 'to_area'],
                ['points' => $measured ? [self::point('MC', 30.96, -1.53), ...array_map(fn ($p) => self::point('CAR', ...$p), self::carApproach())] : [self::point('MC', 30.96, -1.53), [328, 48], self::point('CAR', 7.5, 5.1)], 'kind' => 'to_area'],
                ['points' => [self::point('MC', 2, 2.8), self::point('MC', 27.5, 2.8)], 'kind' => 'lane'],
                ['points' => [self::point('MC', 27.5, 6), self::point('MC', 2, 6)], 'kind' => 'lane'],
                ...($measured ? [
                    ['points' => [self::point('CAR', 10, 7.25), self::point('CAR', 47, 7.25)], 'kind' => 'lane'],
                    ['points' => [self::point('CAR', 9.0, 8.5), self::point('CAR', 11.2, 15.2), self::point('CAR', 11.3, 15.4), self::point('CAR', 11.6, 15.8), self::point('CAR', 16.9, 20.8), self::point('CAR', 17.4, 21.1), self::point('CAR', 17.7, 21.2), self::point('CAR', 23.0, 21.8)], 'kind' => 'lane'],
                    ['points' => [self::point('CAR', 18.5, 21.75), self::point('CAR', 32, 21.75)], 'kind' => 'lane'],
                    ['points' => [self::point('CAR', 4.4, 8.5), self::point('CAR', 1.4, 6.6)], 'kind' => 'road_out'],
                ] : [
                    ['points' => [self::point('CAR', 11, 5.1), self::point('CAR', 46, 5.1)], 'kind' => 'lane'],
                    ['points' => [self::point('CAR', 46, 11.7), self::point('CAR', 11, 11.7)], 'kind' => 'lane'],
                ]),
            ],
            'circulation' => $clearances,
            'circulation_labels' => $measured ? [['text' => 'KEEP CLEAR', 'at' => self::point('CAR', 15.5, 19), 'angle' => 10], ['text' => 'SHARED APPROACH', 'at' => self::point('CAR', 2.9, 7.5), 'angle' => 20]] : [],
            'dimensions' => self::referenceDimensions(),
            'measurement_basis' => self::measurementBasis($measured),
            'proposed_slot_codes' => array_keys(self::slotPositions($measured)),
            'connector' => ['polygon' => [self::point('MC', 30.96, -3.06), self::point('CAR', ...self::CAR_POINTS[7]), self::point('CAR', ...self::CAR_POINTS[6])]],
            'site' => ['polygon' => [[45, 92], [291, -8], [736, 74], [740, 285], [486, 332], [342, 180], [102, 215], [52, 136]]],
            'islands' => [], 'trays' => [],
        ];
    }

    public static function referenceDimensions(): array
    {
        $dimensions = [
            ['id' => 'M-L', 'text' => '30.96 m', 'description' => 'Motorcycle parcel length', 'at' => self::point('MC', 15.48, 14.5), 'angle' => -21, 'from' => self::point('MC', 0, 13.5), 'to' => self::point('MC', 30.96, 13.5)],
            ['id' => 'M-W', 'text' => '8.80 m', 'description' => 'Motorcycle parcel width (road excluded)', 'at' => self::point('MC', -3.4, 4.4), 'angle' => 69, 'from' => self::point('MC', -2.2, 0), 'to' => self::point('MC', -2.2, 8.8)],
            ['id' => 'ROAD', 'text' => '3.06 m', 'description' => 'Separate shared access road width', 'at' => self::point('MC', 4, -4.8), 'angle' => -21, 'from' => self::point('MC', 4, -3.06), 'to' => self::point('MC', 4, 0)],
        ];
        // IDs match the owner's numbered sketch. The short C3 notch uses a
        // compact callout; the upper pink junction is labelled separately.
        foreach ([0 => [56, 9], 1 => [48, 23], 3 => [40, 29], 4 => [10, 25], 8 => [25, -4]] as $edge => $at) {
            $number = $edge === 8 ? 6 : $edge + 1;
            $dimensions[] = ['id' => 'C'.$number, 'text' => number_format(self::CAR_EDGES[$number - 1], 2).' m', 'description' => 'Car boundary '.$number.' (owner-numbered)', 'at' => self::point('CAR', ...$at), 'angle' => 0, 'from' => self::point('CAR', ...self::CAR_POINTS[$edge]), 'to' => self::point('CAR', ...self::CAR_POINTS[($edge + 1) % 9])];
        }
        $dimensions[] = ['id' => 'upper-junction', 'label' => 'Upper junction', 'text' => '3.36 m', 'description' => 'Upper pink junction (oblique reference)', 'at' => self::point('CAR', -7, 2), 'angle' => 0];
        $dimensions[] = ['id' => 'C3', 'text' => '2.60 m', 'description' => 'Car boundary 3 / lower pink segment (owner-corrected)', 'at' => self::point('CAR', 47, 25), 'angle' => 0];

        return $dimensions;
    }

    public static function measurementBasis(bool $measured = false): array
    {
        return [
            'survey_verified' => false, 'scale' => $measured ? 'metric_proposal' : 'slot_diagram', 'units_per_metre' => self::UNITS_PER_METRE,
            'source_image' => '/references/dyci-motorcycle-boundary-20261016.png',
            'lineup_source_image' => '/references/dyci-marked-lineup-20261018.png',
            'lineup_note' => 'Owner sketch: red = proposed car rows, yellow = motorcycle parcel, green = shared access. The sketch guides row placement; cumulative ruler labels do not replace the supplied metre lengths. Live slot colours continue to mean availability. Some marked perimeter strokes cannot be filled without blocking full-size access frontages.',
            'car_source_image' => '/references/dyci-numbered-boundary-20261018.png',
            'boundary_note' => 'Numbers 1–6 match the owner sketch: right 18.42m, lower-right 12.04m, notch 2.60m, lower diagonal 20.18m, long left diagonal 33.37m and top 49.26m. Upper pink junction: 3.36m; lower pink segment is C3, 2.60m. The earlier 3.00m value was corrected by the owner and is not used. The shared motorcycle end is 8.80m. The short NW return is estimated at 4.66m from the image; its length and all boundary bearings need site confirmation. This corrected outline supersedes the earlier eight-consecutive-edge interpretation.',
            'boundary_assumptions' => ['shared_motorcycle_end_m' => 8.8, 'estimated_nw_return_m' => 4.66, 'upper_pink_junction_m' => 3.36, 'lower_pink_opening_m' => 2.6, 'internal_car_aisle_m' => 4.5],
            'car_edges_m' => self::CAR_EDGES,
            'note' => $measured ? 'Proposed layout at one scale: 8 drawing units = 1 metre. Motorcycle dimensions are owner-reported. Car edges 1–6 follow the owner-numbered sketch. The shared motorcycle end is included; the short NW return and all bearings are estimated, not surveyed. Earlier cumulative map-ruler labels have been removed.' : 'Complete slot map: C-01–C-55 and M-01–M-84. Car bay symbols and aisles are schematic, not to scale. Their diagram positions do not establish physical fit. Use Parking layout for full-size bays and owner-reported boundaries.',
            'motorcycle_proposal' => '84 compact bays: 3 rows × 28, each 1.00 × 2.00 m. Two 1.20 m internal aisles, a 2.56 m connecting end aisle, 0.40 m end margin and 0.20 m side margins. Narrow aisles are a walk/push proposal for compact motorcycles and scooters; actual vehicle sizes and manoeuvring must be checked.',
            'car_proposal' => $measured ? '45 full-size independent proposed bays: 18 north-perimeter regular bays including C-42, 15 in interior row A, eight in interior row B and four in the south row. All 45 car bays are regular 2.50 × 5.00 m bays. C-42 has no PWD designation; this prototype has no accessibility eligibility workflow. Internal access frontages are 4.50 m, a tight concept allowance requiring actual turning checks, not a certified standard aisle. No tandem bays are used. This is not a maximum-capacity or vehicle swept-path certification. All 55 car records remain; ten codes have no measured position. The 45-position count is conditional on the reconstructed outline and site validation.' : 'All 55 existing car slots remain as schematic inventory symbols. Diagram sizes are not physical dimensions. Parking layout shows 45 full-size proposed bays on the corrected numbered outline; physical capacity remains subject to site confirmation.',
            'vehicle_measurements' => ['car_width_m' => 2.0447, 'car_length_m' => 4.7752, 'car_mirrors_included' => false, 'motorcycle_width_m' => 0.5969, 'motorcycle_length_m' => 1.905, 'note' => 'Owner-measured maximum dimensions. Car width is body-only; mirrors, wheelbase, steering lock and turning envelope are not measured. These figures do not replace bay dimensions.'],
            'circulation_note' => $measured ? 'Opposing rows share 4.50 m internal aisles. The western passage connects the lower aisle. Every frontage connects geometrically to the shared approach through a nominal 3.06 m clear-envelope grid. Connectivity is not a steering or swept-path test. The 4.50 m aisle choice, mirrors, wheelbase, turning lock, end manoeuvres, accessibility and the estimated NW boundary require campus validation before marking bays.' : 'This optional inventory diagram uses schematic symbols. It does not represent physical aisle widths or a validated vehicle turning path.',
            'inventory' => ['motorcycle_configured' => 84, 'motorcycle_drawn' => 84, 'car_configured' => 55, 'car_drawn' => $measured ? 45 : 55],
            'standards' => [
                ['type' => 'car', 'label' => 'Car bay', 'width_m' => 2.5, 'length_m' => 5.0, 'basis' => 'Philippine NBCP perpendicular/diagonal bay reference. The proposed 4.50 m internal aisle is a tight concept allowance, not a legal-standard or turning-clearance claim; validate it using the actual vehicles.', 'source' => 'National Building Code IRR, Rule VII, Section 707(4)(b), Pasig government copy, p. 100', 'url' => 'https://assets.pasigcity.gov.ph/storage/city_ordinance/2024/10/21/67c675622ba621741059426part%201.pdf'],
                ['type' => 'parallel', 'label' => 'Parallel car bay', 'width_m' => 2.15, 'length_m' => 6.0, 'basis' => 'Philippine NBCP parallel bay reference. Proposed 3.60 m access frontage and end manoeuvres need actual vehicle checks.', 'source' => 'DPWH Department Order 56, series of 2016, Annex A, parking dimensions', 'url' => 'https://www.dpwh.gov.ph/dpwh/sites/default/files/issuances/DO_056_s2016.pdf'],
                ['type' => 'motorcycle', 'label' => 'Compact motorcycle bay', 'width_m' => 1.0, 'length_m' => 2.0, 'basis' => 'Compact design reference from UK council guidance, not a Philippine legal minimum or suitable for every motorcycle. A larger vehicle/bay requirement reduces the proposed count.', 'source' => 'North Tyneside Council, Transport and Highways SPD 2022, Appendix E', 'url' => 'https://my.northtyneside.gov.uk/sites/default/files/web-page-related-files/Transport%20and%20Highways%20SPD%202022.pdf'],
            ],
            'capacity_review' => ['reported_car_capacity' => 55, 'proposed_car_bays' => 45, 'unresolved_car_positions' => 10, 'survey_verified' => false, 'note' => 'The target is not reduced. Forty-five independent positions have a conditional full-size geometric proposal. This is a found arrangement, not a proven maximum. Validate boundary estimates and tight circulation on site. The remaining ten inventory codes have no proposed physical position.'],
            'pathway_note' => 'The shared road follows the existing gate and diagonal route. Its width is 3.06 m in total, separate from the motorcycle parcel; arrows indicate alternating entry/exit, not two simultaneous car lanes. Internal parking aisles have been rearranged. Physical gate and turning clearances remain subject to campus review.',
        ];
    }

    /** Static coordinate alternatives only, safe to include in the redacted map. */
    public static function measuredLayout(): array
    {
        return [
            'geometry' => self::geometry(true),
            'positions' => array_map(fn ($bay) => array_intersect_key($bay, array_flip(['x', 'y', 'w', 'h', 'rotation'])), self::slotPositions(true)),
        ];
    }

    public static function ambiguities(): array
    {
        return [
            self::measurementBasis(true)['motorcycle_proposal'], self::measurementBasis(true)['car_proposal'],
            'Car perimeter bearings are assumed. Lengths alone do not determine the actual parcel shape or prove capacity. All map placements are proposals, not as-built positions.',
            'The default measured proposal draws 45 cars and 84 motorcycles. All 139 inventory records remain in the database; unpositioned codes are retained without invented bay positions. A physical fit for 55 cars is unverified; capacity and operating rules are unchanged.',
        ];
    }

    public static function corrections(): array
    {
        return ['Removed invalid cumulative-ruler labels and old duplicate painted bays. Proposed slots use unique existing PASS codes; no off-map parking pads are added.'];
    }
}
