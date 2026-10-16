<?php

namespace App\Support;

/**
 * Digital version of the DYCI parking layout reference drawing.
 *
 * Coordinates use the schematic drawing space (758 x 399), not surveyed
 * metres. The map keeps its irregular boundaries, diagonal rows
 * and lanes. Each painted bay is listed in the order printed on the drawing
 * together with its printed label and the PASS slot it is bound to.
 *
 * Binding rules (no physical positions are invented):
 *   - A bay is bound to the slot whose number is printed on it.
 *   - An obvious misprint (a duplicate where exactly one number is missing
 *     between its neighbours) is bound to the missing number and listed in
 *     corrections().
 *   - Other duplicates, the PWD bay, the hatched aisle, and bays numbered
 *     beyond the area's capacity stay drawn but are not PASS slots.
 *   - PASS slots whose number is not printed anywhere have no position
 *     (pos_x = NULL), unless a separately identified proposal places them.
 */
class ParkingMapLayout
{
    /** The highway beside the gate is outside campus and is not drawn. */
    public const VIEW_BOX = [40, 0, 720, 400];

    /** Stored coordinates are multiplied by this (parking_slots.pos_* are integers). */
    public const SCALE = 10;

    /**
     * Rows/columns of bays as printed on the drawing.
     *   first/last: centre of the first and last bay (image px)
     *   size:       [length along the row, depth across it] (image px)
     *   labels:     printed text on each bay
     *   bind:       slot number bound to each bay, or null
     *   kinds:      optional per-bay kind: pwd | aisle
     */
    public static function rows(): array
    {
        return [
            // ------------------------------------------------ Motorcycle Parking
            [
                'area' => 'MC', 'name' => 'Row 1-22 (along the IN/OUT road)',
                'first' => [87.5, 135.0], 'last' => [277.0, 63.0], 'size' => [6.5, 14.0],
                'labels' => range(1, 22),
                'bind' => range(1, 22),
            ],
            [
                'area' => 'MC', 'name' => 'Proposed west bank 23-42',
                'first' => [85.0, 148.0], 'last' => [109.0, 272.0], 'size' => [6.5, 14.0],
                'labels' => range(23, 42), 'bind' => range(23, 42),
                'placement_basis' => 'proposed',
            ],
            [
                'area' => 'MC', 'name' => 'Column 43-56',
                'first' => [133.8, 149.5], 'last' => [173.8, 258.0], 'size' => [6.5, 14.0],
                'labels' => [43, 44, 45, 46, 49, 48, 49, 50, 51, 53, 53, 54, 55, 56, 56],
                'bind' => [43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, null],
            ],
            [
                'area' => 'MC', 'name' => 'Column 57-70',
                'first' => [199.5, 126.3], 'last' => [243.8, 238.0], 'size' => [6.5, 14.0],
                'labels' => [57, 58, 59, 60, 61, 62, 63, 64, 66, 66, 66, 67, 68, 69, 70],
                'bind' => [57, 58, 59, 60, 61, 62, 63, 64, 65, 66, null, 67, 68, 69, 70],
            ],
            [
                'area' => 'MC', 'name' => 'Column 71-84',
                'first' => [269.3, 102.5], 'last' => [315.0, 218.0], 'size' => [6.5, 14.0],
                'labels' => [71, 72, 73, 74, 75, 76, 77, 79, 78, 79, 70, 81, 81, 82, 83, 84],
                'bind' => [71, 72, 73, 74, 75, 76, 77, null, 78, 79, 80, 81, null, 82, 83, 84],
            ],

            // ------------------------------------------------------- Car Parking
            [
                'area' => 'CAR', 'name' => 'Row 1-21',
                'first' => [334.9, 49.3], 'last' => [685.7, 105.0], 'size' => [17.0, 24.0],
                'labels' => range(1, 21),
                'bind' => range(1, 21),
            ],
            [
                'area' => 'CAR', 'name' => 'Row 22-41 with PWD bay',
                'first' => [355.7, 106.4], 'last' => [695.0, 167.9], 'size' => [17.2, 24.0],
                'labels' => [22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 39, 41, 'PWD', ''],
                // The unnumbered PWD bay follows 41, so it is read as slot 42 (accessible).
                'bind' => [22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 39, 41, 42, null],
                'kinds' => [18 => 'pwd', 19 => 'aisle'],
            ],
            [
                'area' => 'CAR', 'name' => 'Row 43-55',
                'first' => [382.9, 167.1], 'last' => [635.7, 217.1], 'size' => [18.6, 24.0],
                'labels' => [43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 54, 55],
                'bind' => [43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, null, 55],
            ],
            [
                'area' => 'CAR', 'name' => 'Row 56-70 (beyond 55-slot capacity)',
                'first' => [397.1, 227.1], 'last' => [622.3, 274.3], 'size' => [18.2, 24.0],
                'labels' => [56, 57, 58, 59, 60, 61, 62, 64, 65, 66, 67, 68, 70],
                'bind' => array_fill(0, 13, null),
            ],
            [
                'area' => 'CAR', 'name' => 'East column 75-81 (beyond 55-slot capacity)',
                'first' => [716.5, 112.1], 'last' => [716.5, 254.3], 'size' => [19.4, 16.0],
                'labels' => [75, 76, 77, 78, 79, 80, 81, 81],
                'bind' => array_fill(0, 8, null),
            ],
            [
                'area' => 'CAR', 'name' => 'South-east bays 81-84 (beyond 55-slot capacity)',
                'first' => [672.9, 272.9], 'last' => [718.6, 279.4], 'size' => [14.4, 22.0],
                'labels' => [81, 82, 83, 84],
                'bind' => array_fill(0, 4, null),
            ],
        ];
    }

    /**
     * Every painted bay with its centre, size, rotation and binding.
     *
     * @return array<int, array{area: string, row: string, index: int, x: float, y: float, w: float, h: float, rotation: float, label: string, slot_number: ?int, kind: string, note: ?string}>
     */
    public static function bays(): array
    {
        $bays = [];

        foreach (self::rows() as $row) {
            $n = count($row['labels']);
            [$x1, $y1] = $row['first'];
            [$x2, $y2] = $row['last'];
            $dx = $n > 1 ? ($x2 - $x1) / ($n - 1) : 0;
            $dy = $n > 1 ? ($y2 - $y1) / ($n - 1) : 0;
            $rotation = rad2deg(atan2($dy, $dx));
            $capacity = $row['area'] === 'CAR' ? 55 : 84;

            foreach ($row['labels'] as $i => $label) {
                $bound = $row['bind'][$i];
                $kind = $row['kinds'][$i] ?? ($bound ? 'slot' : 'unbound');
                $note = match (true) {
                    $kind === 'pwd' => 'Accessible (PWD) bay, unnumbered on the drawing'.($bound ? "; read as slot {$bound}" : ''),
                    $kind === 'aisle' => 'Hatched access aisle',
                    $bound !== null && (string) $bound !== (string) $label => "Printed \"{$label}\"; read as {$bound} (misprint)",
                    $bound === null && is_int($label) && $label > $capacity => "Printed \"{$label}\"; beyond the {$capacity}-slot capacity",
                    $bound === null => "Printed \"{$label}\" again (duplicate number)",
                    default => null,
                };

                $bays[] = [
                    'area' => $row['area'],
                    'row' => $row['name'],
                    'index' => $i,
                    'x' => round($x1 + $dx * $i, 1),
                    'y' => round($y1 + $dy * $i, 1),
                    'w' => $bound !== null && $row['area'] === 'CAR' ? ($kind === 'pwd' ? 20.0 : 12.5) : $row['size'][0],
                    'h' => $bound !== null && $row['area'] === 'CAR' ? 25.0 : $row['size'][1],
                    'rotation' => round($rotation, 1),
                    'label' => (string) $label,
                    'slot_number' => $bound,
                    'kind' => $kind,
                    'note' => $note,
                    'pwd' => $kind === 'pwd',
                    'placement_basis' => $row['placement_basis'] ?? 'reference_drawing',
                ];
            }
        }

        return $bays;
    }

    /**
     * Slot code => bay, for bays bound to a PASS slot.
     */
    public static function slotPositions(): array
    {
        $positions = [];

        foreach (self::bays() as $bay) {
            if ($bay['slot_number'] !== null) {
                $prefix = $bay['area'] === 'CAR' ? 'C' : 'M';
                $positions[sprintf('%s-%02d', $prefix, $bay['slot_number'])] = $bay;
            }
        }

        return $positions;
    }

    /**
     * Static drawing: boundaries, the shared gate and IN/OUT road, internal
     * lanes with direction arrows, and reference dimension callouts. No occupancy data. The highway outside the
     * gate is intentionally omitted.
     */
    public static function geometry(): array
    {
        return [
            'areas' => [
                ['area' => 'MC', 'polygon' => [[70, 126], [300, 30], [377, 214], [100, 278]], 'label' => 'MOTORCYCLE PARKING (84 SLOTS)', 'label_at' => [196, 116], 'label_angle' => -21, 'sign_at' => [104, 212]],
                ['area' => 'CAR', 'polygon' => [[318, 33], [746, 86], [746, 302], [642, 284], [404, 328]], 'label' => 'CAR PARKING (55 SLOTS)', 'label_at' => [470, 102], 'label_angle' => 9, 'sign_at' => [540, 46]],
            ],
            // One shared IN/OUT road from the single gate; entering traffic keeps to the north lane.
            'road' => ['polygon' => [[66, 104], [296, 16], [302, 30], [72, 124]], 'label' => 'IN / OUT', 'label_at' => [186, 66], 'label_angle' => -21],
            'gate' => ['at' => [69, 114], 'angle' => -21, 'label' => 'MAIN ENTRANCE / EXIT', 'sublabel' => 'Shared gate for cars and motorcycles', 'label_at' => [128, 24]],
            'lanes' => [
                // Road: in (eastbound, north lane) and out (westbound, south lane).
                ['points' => [[136.5, 82.2], [171, 68.7]], 'kind' => 'road_in'],
                ['points' => [[228.5, 46.4], [263, 32.9]], 'kind' => 'road_in'],
                ['points' => [[208.5, 63.5], [174, 77.4]], 'kind' => 'road_out'],
                ['points' => [[128, 95.9], [93.5, 109.8]], 'kind' => 'road_out'],
                // From the shared road to each area.
                ['points' => [[296, 24], [318, 30], [338, 44]], 'kind' => 'to_area', 'label' => 'Cars', 'label_at' => [322, 22], 'label_angle' => 0],
                ['points' => [[290, 37], [301, 66], [317, 112]], 'kind' => 'to_area', 'label' => 'Motorcycles', 'label_at' => [309, 72], 'label_angle' => 69],
                // Motorcycle circulation.
                ['points' => [[279, 228], [234, 114]], 'kind' => 'lane', 'reverse' => true],
                ['points' => [[209, 248], [167, 138]], 'kind' => 'lane'],
                ['points' => [[262, 87], [142, 133]], 'kind' => 'lane'],
                // Car aisles are one-way, westbound.
                ['points' => [[690, 136], [362, 80]], 'kind' => 'lane'],
                ['points' => [[640, 192], [380, 139]], 'kind' => 'lane'],
                ['points' => [[624, 247], [394, 199]], 'kind' => 'lane'],
            ],
            'dimensions' => self::referenceDimensions(),
            'measurement_basis' => self::measurementBasis(),
            'proposed_slot_codes' => array_map(fn ($n) => sprintf('M-%02d', $n), range(23, 42)),
            // The wedge between the two areas, where the IN/OUT road meets
            // both of them, is one paved circulation lane (drawn in white on
            // the reference). No bay touches it.
            'connector' => ['polygon' => [[294, 14], [334, 26], [364, 190], [340, 190], [300, 30]]],
            // Landscaped campus ground around the paved facility.
            'site' => ['polygon' => [[46, 90], [292, 2], [758, 70], [760, 316], [644, 298], [414, 342], [352, 304], [296, 318], [176, 320], [96, 300], [56, 130]]],
            // Planted islands only where the drawing leaves unused space.
            // The west bank uses the former decorative island; circulation is unchanged.
            'islands' => [],
            // Unmapped inventory is not rendered as invented physical bays.
            'trays' => [],
        ];
    }

    /**
     * Where the drawing is ambiguous or disagrees with the system capacity.
     */
    public static function ambiguities(): array
    {
        return [
            'M-23 to M-42 are proposed positions along the west bank inside the motorcycle area. The supplied site photo does not locate individual bays; campus confirmation is still required.',
            'Car slots C-38 and C-40 are not printed on the drawing (row 22-41 shows 37, 39, 41, then the PWD bay); their inventory records are retained without drawing additional bays.',
            'The unnumbered PWD bay directly after 41 is read as C-42, the accessible slot within the 55-slot capacity. Automatic assignment gives it out last.',
            'The car area is titled "55 slots" but shows about 79 painted bays numbered up to 84; bays beyond 55 are drawn as unnumbered stalls and are not PASS slots.',
            'The motorcycle columns contain duplicate or out-of-order numbers; obvious misprints were read as the missing number (see corrections) and the remaining duplicates are drawn but not bound.',
            'The drawing marks a "Motorcycle only gate"; per the operating rule there is a single shared Main Entrance/Exit, so that marker is shown only as the internal route to the motorcycle area.',
            'The highway outside the Main Entrance/Exit is not part of the parking site and is not shown.',
        ];
    }

    /** Lengths transcribed from the owner's annotated aerial image; not survey-certified. */
    public static function referenceDimensions(): array
    {
        return [
            ['id' => 'D1', 'text' => '160.00 m', 'description' => 'North-west long edge', 'at' => [166, 31], 'angle' => -21, 'from' => [57, 92], 'to' => [287, 4]],
            ['id' => 'D2', 'text' => '22.67 m', 'description' => 'Short north-west segment', 'at' => [295, -23], 'angle' => 0, 'from' => [289, -9], 'to' => [314, 8]],
            ['id' => 'D3', 'text' => '20.00 m', 'description' => 'First label on the northern edge', 'at' => [386, 3], 'angle' => 7, 'from' => [320, 8], 'to' => [463, 26]],
            ['id' => 'D4', 'text' => '40.00 m', 'description' => 'Second label on the northern edge', 'at' => [607, 32], 'angle' => 7, 'from' => [465, 26], 'to' => [750, 61]],
            ['id' => 'D5', 'text' => '60.00 m', 'description' => 'Eastern edge', 'at' => [790, 194], 'angle' => 90, 'from' => [773, 86], 'to' => [773, 302]],
            ['id' => 'D6', 'text' => '80.00 m', 'description' => 'Short south-east edge', 'at' => [703, 340], 'angle' => 10, 'from' => [746, 327], 'to' => [642, 309]],
            ['id' => 'D7', 'text' => '100.00 m', 'description' => 'South-west edge of car area', 'at' => [520, 363], 'angle' => -10, 'from' => [642, 311], 'to' => [404, 355]],
            ['id' => 'D8', 'text' => '120.00 m', 'description' => 'Sloping dividing edge', 'at' => [364, 277], 'angle' => 73, 'from' => [302, 39], 'to' => [388, 334]],
            ['id' => 'D9', 'text' => '200.00 m', 'description' => 'South edge of motorcycle area', 'at' => [232, 289], 'angle' => -13, 'from' => [100, 300], 'to' => [377, 236]],
        ];
    }

    public static function measurementBasis(): array
    {
        return [
            'survey_verified' => false,
            'scale' => 'schematic',
            'source_image' => '/references/dyci-site-dimensions-20261017.png',
            'note' => 'Boundary labels are copied from the supplied aerial image. Bay symbols are proportional design templates; the overall site is not to scale. Lengths alone do not establish bearings, clearances or survey accuracy.',
            'standards' => [
                ['type' => 'car', 'label' => 'Car bay (perpendicular / diagonal)', 'width_m' => 2.5, 'length_m' => 5.0,
                    'basis' => 'Philippine building-code dimension, adopted as the bay template; actual site dimensions remain unverified.',
                    'source' => 'National Building Code IRR, Rule VII, Section 707(4)(b), reproduced in the Pasig City government copy, p. 100',
                    'url' => 'https://assets.pasigcity.gov.ph/storage/city_ordinance/2024/10/21/67c675622ba621741059426part%201.pdf'],
                ['type' => 'motorcycle', 'label' => 'Motorcycle bay', 'width_m' => 1.3, 'length_m' => 2.8,
                    'basis' => 'Adopted prototype design target from UK council guidance; not asserted as a Philippine legal minimum or existing DYCI measurement.',
                    'source' => 'Rotherham Council, Transport Assessments, Travel Plans and Parking Standards, paragraph 78',
                    'url' => 'https://moderngov.rotherham.gov.uk/documents/s131792/Appendix%206%20-%20SPD_TransportAssessments.pdf'],
                ['type' => 'accessible', 'label' => 'Accessible car bay', 'width_m' => 4.0, 'length_m' => 5.0,
                    'basis' => '2024 amended BP 344 IRR bay dimensions. Also requires a 1.20 m clear front walkway and appropriate access. This drawing does not certify compliance.',
                    'source' => 'NCDA, 2024 revised BP 344 IRR, Section A.6 Parking',
                    'url' => 'https://ncda.gov.ph/revised-2024-rules-and-regulations-implementing-batas-pambansa-344/'],
            ],
            'pathway_note' => 'Existing pathway geometry and traffic arrows are retained. Actual aisle and gate clear widths have not been measured; no compliance or vehicle turning-clearance claim is made.',
        ];
    }

    /** Bays whose printed number was read as a different (missing) number. */
    public static function corrections(): array
    {
        return array_values(array_filter(array_map(
            fn ($b) => $b['slot_number'] !== null && ! $b['pwd'] && (string) $b['slot_number'] !== $b['label']
                ? sprintf('%s: printed "%s" read as %s-%02d', $b['row'], $b['label'], $b['area'] === 'CAR' ? 'C' : 'M', $b['slot_number'])
                : null,
            self::bays()
        )));
    }
}
