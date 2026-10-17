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

    public static function bays(bool $measured = false): array
    {
        $bays = [];
        $add = function (string $area, int $number, float $x, float $y, float $w, float $h, string $row, bool $pwd = false) use (&$bays, $measured) {
            [$gx, $gy] = self::point($area, $x + $w / 2, $y + $h / 2);
            $bays[] = ['area' => $area, 'row' => $row, 'index' => $number - 1, 'x' => round($gx, 3), 'y' => round($gy, 3), 'w' => $w * self::UNITS_PER_METRE, 'h' => $h * self::UNITS_PER_METRE, 'rotation' => $area === 'CAR' ? 10.0 : -21.0, 'label' => (string) $number, 'slot_number' => $number, 'kind' => $pwd ? 'pwd' : 'slot', 'pwd' => $pwd, 'placement_basis' => $area === 'CAR' && ! $measured ? 'diagram' : 'proposed', 'note' => 'Proposed position; physical layout and manoeuvring require campus confirmation.'];
        };
        // 28.00 m of bays, 0.40 m end margin and 2.56 m connecting end aisle.
        // Width: 0.20 + 2.00 + 1.20 + 2.00 + 1.20 + 2.00 + 0.20 = 8.80 m.
        foreach ([0.2, 3.4, 6.6] as $row => $y) {
            foreach (range(0, 27) as $column) {
                $add('MC', $row * 28 + $column + 1, 0.4 + $column, $y, 1.0, 2.0, 'Compact motorcycle row '.($row + 1));
            }
        }
        if ($measured) {
            // A conservative car proposal, NOT a maximum-capacity calculation.
            // Keep the existing C-42 accessible designation; do not change inventory policy.
            $add('CAR', 42, 7.5, 0, 4.0, 5.0, 'Proposed north row', true);
            foreach (range(1, 15) as $i) {
                $add('CAR', $i, 11.5 + ($i - 1) * 2.5, 0, 2.5, 5.0, 'Proposed north row');
            }
            foreach (range(16, 25) as $i) {
                $add('CAR', $i, 12 + ($i - 16) * 2.5, 12.2, 2.5, 5.0, 'Proposed south-west row');
            }
            // A separate bank avoids the concave notch; it is not paved over.
            foreach (range(26, 29) as $i) {
                $add('CAR', $i, 38.75 + ($i - 26) * 2.5, 12.2, 2.5, 5.0, 'Proposed south-east row');
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
            $clearances[] = ['polygon' => self::rectangle('CAR', 7.5, 6.2, 41.5, 6), 'kind' => 'car_aisle', 'width_m' => 6.0];
            $clearances[] = ['polygon' => self::rectangle('CAR', 7.5, 5, 4, 1.2), 'kind' => 'walkway', 'width_m' => 1.2];
        } else {
            foreach ([[7.5, 3.6, 39, 3], [10.5, 10.2, 38, 3], [7.5, 3.6, 3, 6.6]] as $rect) {
                $clearances[] = ['polygon' => self::rectangle('CAR', ...$rect), 'kind' => 'diagram_aisle'];
            }
        }

        return [
            'areas' => [
                ['area' => 'MC', 'polygon' => $mc, 'label' => '84 COMPACT MOTORCYCLE BAYS · PROPOSED', 'label_at' => self::point('MC', 14.2, 9.9), 'label_angle' => -21, 'sign_at' => self::point('MC', 15.48, 10.2)],
                ['area' => 'CAR', 'polygon' => $car, 'label' => $measured ? '30 CAR BAYS · MEASURED PROPOSAL' : '55 CAR SLOTS · DIAGRAM', 'label_at' => self::point('CAR', 28, 22.4), 'label_angle' => 10, 'sign_at' => self::point('CAR', 28, -1.4)],
            ],
            'road' => ['polygon' => self::rectangle('MC', 0, -3.06, 30.96, 3.06), 'label' => 'SHARED ACCESS · 3.06 m', 'label_at' => self::point('MC', 15.5, -1.53), 'label_angle' => -21, 'width_m' => 3.06, 'traffic' => 'alternating'],
            'gate' => ['at' => [69, 114], 'angle' => -21, 'label' => 'MAIN ENTRANCE / EXIT', 'sublabel' => 'Shared controlled access', 'label_at' => [95, 27]],
            'lanes' => [
                ['points' => [self::point('MC', 6, -1.53), self::point('MC', 10, -1.53)], 'kind' => 'road_in'],
                ['points' => [self::point('MC', 25, -1.53), self::point('MC', 21, -1.53)], 'kind' => 'road_out'],
                ['points' => [self::point('MC', 29.68, -1.53), self::point('MC', 29.68, 7.8)], 'kind' => 'to_area'],
                ['points' => [self::point('MC', 30.96, -1.53), [328, 48], self::point('CAR', 7.5, $measured ? 9.2 : 5.1)], 'kind' => 'to_area'],
                ['points' => [self::point('MC', 2, 2.8), self::point('MC', 27.5, 2.8)], 'kind' => 'lane'],
                ['points' => [self::point('MC', 27.5, 6), self::point('MC', 2, 6)], 'kind' => 'lane'],
                ['points' => [self::point('CAR', 11, $measured ? 9.2 : 5.1), self::point('CAR', 46, $measured ? 9.2 : 5.1)], 'kind' => 'lane'],
                ...($measured ? [] : [['points' => [self::point('CAR', 46, 11.7), self::point('CAR', 11, 11.7)], 'kind' => 'lane']]),
            ],
            'circulation' => $clearances,
            'dimensions' => self::referenceDimensions(),
            'measurement_basis' => self::measurementBasis($measured),
            'proposed_slot_codes' => array_keys(self::slotPositions($measured)),
            'connector' => ['polygon' => [[299, 14], [333, 28], self::point('CAR', 8, $measured ? 9.2 : 5.1), self::point('CAR', 3, $measured ? 9.2 : 5.1), [302, 42]]],
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
            'car_source_image' => '/references/dyci-car-boundary-20261016.png',
            'car_edges_m' => self::CAR_EDGES,
            'note' => $measured ? 'Proposed layout at one scale: 8 drawing units = 1 metre. Motorcycle dimensions are owner-reported. Car edge lengths follow the supplied order, starting at the right edge; angles are estimated from the image, not surveyed. Earlier cumulative map-ruler labels have been removed.' : 'Complete slot map: C-01–C-55 and M-01–M-84. Car bay symbols and aisles are schematic, not to scale. Their diagram positions do not establish physical fit. Use Measured proposal for metre-sized bays and owner-reported boundaries.',
            'motorcycle_proposal' => '84 compact bays: 3 rows × 28, each 1.00 × 2.00 m. Two 1.20 m internal aisles, a 2.56 m connecting end aisle, 0.40 m end margin and 0.20 m side margins. Narrow aisles are a walk/push proposal for compact motorcycles and scooters; actual vehicle sizes and manoeuvring must be checked.',
            'car_proposal' => $measured ? '30 car bays shown, including the existing C-42 accessible designation, with a 6.00 m aisle and a 1.20 m front walkway at C-42. This is one conservative arrangement, not the maximum site capacity. The 55-car inventory is retained; 25 car codes are omitted only from this measured view. Fitting all 55 has not been demonstrated. Accessible space count and continuous accessible route still require review.' : 'All 55 existing car slots are mapped as separate live diagram symbols. Slot IDs, history and availability are shared with the measured proposal. Diagram bay sizes are not physical dimensions. The measured proposal currently draws 30 car bays; a physical 55-car arrangement remains unverified.',
            'inventory' => ['motorcycle_configured' => 84, 'motorcycle_drawn' => 84, 'car_configured' => 55, 'car_drawn' => $measured ? 30 : 55],
            'standards' => [
                ['type' => 'car', 'label' => 'Car bay', 'width_m' => 2.5, 'length_m' => 5.0, 'basis' => 'Philippine NBCP perpendicular/diagonal bay reference. Six-metre car aisle is a proposed design allowance; turning movements require site validation.', 'source' => 'National Building Code IRR, Rule VII, Section 707(4)(b), Pasig government copy, p. 100', 'url' => 'https://assets.pasigcity.gov.ph/storage/city_ordinance/2024/10/21/67c675622ba621741059426part%201.pdf'],
                ['type' => 'motorcycle', 'label' => 'Compact motorcycle bay', 'width_m' => 1.0, 'length_m' => 2.0, 'basis' => 'Compact design reference from UK council guidance, not a Philippine legal minimum or suitable for every motorcycle. A larger vehicle/bay requirement reduces the proposed count.', 'source' => 'North Tyneside Council, Transport and Highways SPD 2022, Appendix E', 'url' => 'https://my.northtyneside.gov.uk/sites/default/files/web-page-related-files/Transport%20and%20Highways%20SPD%202022.pdf'],
                ['type' => 'accessible', 'label' => 'Accessible car bay', 'width_m' => 4.0, 'length_m' => 5.0, 'basis' => '2024 BP 344 IRR bay dimensions with a 1.20 m front walkway. This proposal does not establish the required accessible count, route, slope or full compliance.', 'source' => 'NCDA, revised 2024 BP 344 IRR, Section A.6 Parking', 'url' => 'https://ncda.gov.ph/revised-2024-rules-and-regulations-implementing-batas-pambansa-344/'],
            ],
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
            'All 139 inventory records appear on the default slot map. Car symbols are schematic. The separate measured proposal does not prove a physical fit for 55 cars; capacity and operating rules are unchanged.',
        ];
    }

    public static function corrections(): array
    {
        return ['Removed invalid cumulative-ruler labels and old duplicate painted bays. Proposed slots use unique existing PASS codes; no off-map parking pads are added.'];
    }
}
