<?php

namespace App\Support;

/** Proposed metric layout, not a surveyed or approved campus capacity plan. */
class ParkingMapLayout
{
    public const VIEW_BOX = [35, -15, 740, 380];

    /** Integer database-coordinate precision; separate from the metric drawing scale. */
    public const SCALE = 10;

    public const UNITS_PER_METRE = 8;

    /** The owner's eight lengths, in the supplied order; bearings are reconstructed. */
    public const CAR_EDGES = [18.42, 12.04, 2.60, 20.18, 33.37, 3.00, 3.36, 49.26];

    private const CAR_POINTS = [[49.26, 0], [50.5394786145, 18.3755090943], [38.5674419040, 19.6529819909], [38.6800712198, 17.0554226293], [22.2689481670, 28.7988224838], [-0.5080178134, 4.4109828761], [-2.5378816542, 2.2019892619], [0, 0]];

    /** Local metres -> the shared drawing coordinate system. */
    private static function point(string $area, float $x, float $y): array
    {
        $angle = deg2rad($area === 'CAR' ? 10 : -21);
        $origin = $area === 'CAR' ? [324, 30] : [69, 114];
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
            ['x' => 11.000000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 13.500000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 16.000000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 18.500000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 21.000000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 23.500000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 26.000000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 28.500000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 31.000000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 33.500000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 36.000000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 38.500000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 41.000000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 43.500000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 46.000000000, 'y' => 0.060000000, 'angle' => 0, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'North perimeter', 'type' => 'perpendicular'],
            ['x' => 18.000000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 20.500000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 23.000000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 25.500000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 28.000000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 30.500000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 33.000000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 35.500000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 38.000000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 40.500000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 43.000000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 45.500000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 48.000000000, 'y' => 16.060000000, 'angle' => 180, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Interior row A', 'type' => 'perpendicular'],
            ['x' => 19.000000000, 'y' => 16.060000000, 'angle' => 0, 'width' => 6, 'depth' => 2.15, 'aisle' => 3.6, 'row' => 'Interior row B', 'type' => 'parallel'],
            ['x' => 25.000000000, 'y' => 16.060000000, 'angle' => 0, 'width' => 6, 'depth' => 2.15, 'aisle' => 3.6, 'row' => 'Interior row B', 'type' => 'parallel'],
            ['x' => 22.293356545, 'y' => 28.707694930, 'angle' => -133, 'width' => 2.5, 'depth' => 5, 'aisle' => 6, 'row' => 'Perimeter', 'type' => 'perpendicular'],
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
        return [[-1.27, 1.10], [1.4, 4.7], [4.4, 8.7], [10, 8.7]];
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
            $add('CAR', 42, 7, 0.06, 4.0, 5.0, 'North row / accessible', true);
            foreach (self::carProposal() as $i => $bay) {
                $add('CAR', $i + 1, $bay['x'], $bay['y'], $bay['width'], $bay['depth'], $bay['row'], false, $bay['angle']);
            }
        } else {
            // All 55 existing car codes have separate diagram symbols. These are
            // deliberately NOT metre-sized bays; physical fit remains a separate view.
            $add('CAR', 42, 7.5, 0, 2.8, 3.6, 'Diagram north row', true);
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
            $clearances[] = ['polygon' => self::carRectangle([7, 6.26], 4, 6), 'kind' => 'car_aisle', 'width_m' => 6.0];
            $clearances[] = ['polygon' => self::carRectangle([7, 5.06], 4, 1.2), 'kind' => 'walkway', 'width_m' => 1.2];
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
                ['area' => 'CAR', 'polygon' => $car, 'label' => $measured ? '32 FULL-SIZE CAR BAYS · PROPOSED' : '55 CAR SLOTS · DIAGRAM', 'label_at' => self::point('CAR', 27, 13.3), 'label_angle' => 10, 'sign_at' => self::point('CAR', 28, -1.4)],
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
                    ['points' => [self::point('CAR', 11, 7.3), self::point('CAR', 45, 7.3)], 'kind' => 'lane'],
                    ['points' => [self::point('CAR', 45, 9.3), self::point('CAR', 11, 9.3)], 'kind' => 'lane'],
                    ['points' => [self::point('CAR', 9.0, 8.7), self::point('CAR', 14.15, 16.9), self::point('CAR', 14.3, 17.1), self::point('CAR', 14.5, 17.3), self::point('CAR', 14.7, 17.45), self::point('CAR', 18.3, 19.65), self::point('CAR', 18.5, 19.75), self::point('CAR', 27.3, 22.35)], 'kind' => 'lane'],
                    ['points' => [self::point('CAR', 4.4, 8.7), self::point('CAR', 1.4, 4.7)], 'kind' => 'road_out'],
                ] : [
                    ['points' => [self::point('CAR', 11, 5.1), self::point('CAR', 46, 5.1)], 'kind' => 'lane'],
                    ['points' => [self::point('CAR', 46, 11.7), self::point('CAR', 11, 11.7)], 'kind' => 'lane'],
                ]),
            ],
            'circulation' => $clearances,
            'circulation_labels' => $measured ? [['text' => 'KEEP CLEAR', 'at' => self::point('CAR', 14, 16), 'angle' => 10], ['text' => 'SHARED APPROACH', 'at' => self::point('CAR', 2.9, 6.7), 'angle' => 20]] : [],
            'dimensions' => self::referenceDimensions(),
            'measurement_basis' => self::measurementBasis($measured),
            'proposed_slot_codes' => array_keys(self::slotPositions($measured)),
            'connector' => ['polygon' => $measured ? [self::point('MC', 30.96, -3.06), self::point('CAR', -0.115, 0.10), self::point('CAR', -2.425, 2.10), self::point('MC', 30.96, 0)] : [[299, 14], [333, 28], self::point('CAR', 8, 5.1), self::point('CAR', 3, 5.1), [302, 42]]],
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
        // All eight measurements appear in the evidence table. Short edge callouts
        // stay in that table so they do not overlap adjacent bays on small displays.
        foreach ([0 => [55, 9], 1 => [45, 22], 3 => [30, 27.5], 4 => [7, 19], 7 => [25, -4]] as $i => $at) {
            $dimensions[] = ['id' => 'C'.($i + 1), 'text' => number_format(self::CAR_EDGES[$i], 2).' m', 'description' => 'Car boundary edge '.($i + 1).' (assumed order)', 'at' => self::point('CAR', ...$at), 'angle' => 0, 'from' => self::point('CAR', ...self::CAR_POINTS[$i]), 'to' => self::point('CAR', ...self::CAR_POINTS[($i + 1) % 8])];
        }

        return $dimensions;
    }

    public static function measurementBasis(bool $measured = false): array
    {
        return [
            'survey_verified' => false, 'scale' => $measured ? 'metric_proposal' : 'slot_diagram', 'units_per_metre' => self::UNITS_PER_METRE,
            'source_image' => '/references/dyci-motorcycle-boundary-20261016.png',
            'lineup_source_image' => '/references/dyci-marked-lineup-20261018.png',
            'lineup_note' => 'Owner sketch: red = proposed car rows, yellow = motorcycle parcel, green = shared access. The sketch guides row placement; cumulative ruler labels do not replace the supplied metre lengths. Live slot colours continue to mean availability. Some marked perimeter strokes cannot be filled without blocking full-size access frontages.',
            'car_source_image' => '/references/dyci-car-boundary-20261016.png',
            'car_edges_m' => self::CAR_EDGES,
            'note' => $measured ? 'Proposed layout at one scale: 8 drawing units = 1 metre. Motorcycle dimensions are owner-reported. Car edge lengths follow the supplied order, starting at the right edge; angles are estimated from the image, not surveyed. Earlier cumulative map-ruler labels have been removed.' : 'Complete slot map: C-01–C-55 and M-01–M-84. Car bay symbols and aisles are schematic, not to scale. Their diagram positions do not establish physical fit. Use Parking layout for full-size bays and owner-reported boundaries.',
            'motorcycle_proposal' => '84 compact bays: 3 rows × 28, each 1.00 × 2.00 m. Two 1.20 m internal aisles, a 2.56 m connecting end aisle, 0.40 m end margin and 0.20 m side margins. Narrow aisles are a walk/push proposal for compact motorcycles and scooters; actual vehicle sizes and manoeuvring must be checked.',
            'car_proposal' => $measured ? '32 full-size proposed bays following the owner-marked lineup: 15 north-perimeter regular bays, 13 in interior row A, two parallel bays in interior row B, one lower-perimeter regular bay and C-42 accessible near the shared approach. Regular bays are 2.50 × 5.00 m; parallel bays are 2.15 × 6.00 m; C-42 is 4.00 × 5.00 m with a 1.20 m front walkway. Perpendicular bays retain 6.00 m access frontages; parallel bays have proposed 3.60 m frontages. The western passage stays clear to connect the lower aisle; the accessible frontage is kept wholly within the parcel. This is not a maximum-capacity or vehicle swept-path certification. All 55 car records remain; 23 codes have no measured position. Physical fit for 55 cars has not been established.' : 'All 55 existing car slots are retained as schematic inventory symbols. Diagram sizes are not physical dimensions. Parking layout shows 32 full-size proposed bays based on the marked lineup; physical fit for all 55 cars remains unverified.',
            'vehicle_measurements' => ['car_width_m' => 2.0447, 'car_length_m' => 4.7752, 'car_mirrors_included' => false, 'motorcycle_width_m' => 0.5969, 'motorcycle_length_m' => 1.905, 'note' => 'Owner-measured maximum dimensions. Car width is body-only; mirrors, wheelbase, steering lock and turning envelope are not measured. These figures do not replace bay dimensions.'],
            'circulation_note' => $measured ? 'The outer parcel is retained. The north-perimeter and first interior row share a 6.00 m aisle. Two parallel bays form the shorter second interior row. A western passage connects its lower aisle; end manoeuvres remain provisional. A 3.06 m clear-envelope grid connects every proposed access frontage to the shared approach. This geometric check is not vehicle turning analysis. Confirm mirrors, wheelbase, steering geometry, end manoeuvres, accessibility and the shared junction on site.' : 'This optional inventory diagram uses schematic symbols. It does not represent physical aisle widths or a validated vehicle turning path.',
            'inventory' => ['motorcycle_configured' => 84, 'motorcycle_drawn' => 84, 'car_configured' => 55, 'car_drawn' => $measured ? 32 : 55],
            'standards' => [
                ['type' => 'car', 'label' => 'Car bay', 'width_m' => 2.5, 'length_m' => 5.0, 'basis' => 'Philippine NBCP perpendicular/diagonal bay reference. Six-metre car aisle is a proposed design allowance; turning movements require site validation.', 'source' => 'National Building Code IRR, Rule VII, Section 707(4)(b), Pasig government copy, p. 100', 'url' => 'https://assets.pasigcity.gov.ph/storage/city_ordinance/2024/10/21/67c675622ba621741059426part%201.pdf'],
                ['type' => 'parallel', 'label' => 'Parallel car bay', 'width_m' => 2.15, 'length_m' => 6.0, 'basis' => 'Philippine NBCP parallel bay reference. Proposed 3.60 m access frontage and end manoeuvres need actual vehicle checks.', 'source' => 'DPWH Department Order 56, series of 2016, Annex A, parking dimensions', 'url' => 'https://www.dpwh.gov.ph/dpwh/sites/default/files/issuances/DO_056_s2016.pdf'],
                ['type' => 'motorcycle', 'label' => 'Compact motorcycle bay', 'width_m' => 1.0, 'length_m' => 2.0, 'basis' => 'Compact design reference from UK council guidance, not a Philippine legal minimum or suitable for every motorcycle. A larger vehicle/bay requirement reduces the proposed count.', 'source' => 'North Tyneside Council, Transport and Highways SPD 2022, Appendix E', 'url' => 'https://my.northtyneside.gov.uk/sites/default/files/web-page-related-files/Transport%20and%20Highways%20SPD%202022.pdf'],
                ['type' => 'accessible', 'label' => 'Accessible car bay', 'width_m' => 4.0, 'length_m' => 5.0, 'basis' => '2024 BP 344 IRR bay dimensions with a 1.20 m front walkway. This proposal does not establish the required accessible count, route, slope or full compliance.', 'source' => 'NCDA, revised 2024 BP 344 IRR, Section A.6 Parking', 'url' => 'https://ncda.gov.ph/revised-2024-rules-and-regulations-implementing-batas-pambansa-344/'],
            ],
            'capacity_review' => ['reported_car_capacity' => 55, 'proposed_car_bays' => 32, 'unresolved_car_positions' => 23, 'survey_verified' => false, 'note' => 'The target is not reduced. Thirty-two positions have a full-size geometric proposal; this is a found arrangement, not a proven maximum. Reaching 55 requires actual parking-position evidence and validation of circulation, vehicle sizes and any tandem or blocked parking.'],
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
            self::measurementBasis()['motorcycle_proposal'], self::measurementBasis()['car_proposal'],
            'Car perimeter bearings are assumed. Lengths alone do not determine the actual parcel shape or prove capacity. All map placements are proposals, not as-built positions.',
            'The default measured proposal draws 32 cars and 84 motorcycles. All 139 inventory records remain in the optional Inventory diagram. A physical fit for 55 cars is unverified; capacity and operating rules are unchanged.',
        ];
    }

    public static function corrections(): array
    {
        return ['Removed invalid cumulative-ruler labels and old duplicate painted bays. Proposed slots use unique existing PASS codes; no off-map parking pads are added.'];
    }
}
