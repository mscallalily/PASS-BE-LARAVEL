<?php

namespace App\Services\Vehicles;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Reads the vehicle registration fields used by the PASS form from the OCR
 * text of an LTO Official Receipt (OR) or Certificate of Registration (CR).
 *
 * Values are taken only from labeled fields ("PLATE NO.", "MAKE", "SERIES",
 * "COLOR", "YEAR MODEL", "BODY TYPE" and a registration validity label); a
 * field without a readable label is reported as missing, never guessed.
 * Owner names and other document details are deliberately not extracted.
 *
 * The label set follows the printed LTO OR/CR headings. It has not been
 * calibrated against every OR/CR layout in circulation; unmatched layouts
 * yield missing fields, which keeps the submission unapproved.
 */
class VehicleDocumentFields
{
    private bool $sameLine = false;

    /** Labels that may share a line with another field; used to cut values. */
    private const LABELS = [
        'plate' => 'PLATE\s*(?:NO\.?|NUMBER|#)',
        'make' => 'MAKE(?!\s*\/)',
        'model' => '(?:SERIES|(?<!YEAR\s)(?<!YEAR)MODEL(?!\s*YEAR))',
        'color' => 'COLOU?R',
        'year' => 'YEAR\s*MODEL',
        // "CLASSIFICATION" on an OR/CR is Private / For Hire, not the body type.
        'body_type' => '(?:BODY\s*TYPE|TYPE\s*OF\s*BODY|VEHICLE\s*TYPE)',
        'registration_expiry' => '(?:REGISTRATION\s*EXPIR\w*(?:\s*DATE)?|REG\.?\s*EXPIR\w*(?:\s*DATE)?|VALID\s*UNTIL|VALIDITY(?:\s*DATE)?|EXPIR(?:Y|ATION)\s*DATE)',
    ];

    /** Other printed labels that end a value on the same line. */
    private const STOP_LABELS = ['MV\s*FILE', 'FILE\s*NO', 'ENGINE', 'CHASSIS', 'FUEL', 'OWNER', 'ADDRESS', 'GROSS', 'NET\s*(?:WT|WEIGHT)', 'SHIPPING', 'PISTON', 'CYLINDER', 'O\.?R\.?\s*NO', 'C\.?R\.?\s*NO', 'DATE', 'CLASSIFICATION', 'AIRCON', 'TRANSACTION', '\|'];

    /**
     * Grid labels of the CR (and OR) as letters only, after common OCR
     * slips (0 for O). Field labels give a value; the others only bound the
     * neighbouring cells and are never taken as a value.
     */
    private const GRID_LABELS = [
        // "PLATE" alone (its "NO." lost) counts only at the start of a line, so the
        // OR's "Cost of plate" in the payment column is never a plate label.
        'plate' => ['PLATENO', 'PLATENUMBER', 'PLATE'],
        'make' => ['MAKEBRAND', 'MAKE', 'BRAND'],
        'model' => ['MODELSERIES', 'SERIES', 'MODEL'],
        'color' => ['COLOR', 'COLOUR'],
        'year' => ['YEARMODEL'],
        'body_type' => ['BODYTYPE', 'VEHICLETYPE'],
    ];

    private const GRID_STOPS = ['ENGINENO', 'CHASSISNO', 'ENGINE', 'CHASSIS', 'OWNERS', 'OWNER', 'VIN', 'FILENO', 'VEHICLECATEGORY', 'CLASSIFICATION', 'PASSENGERCAPACITY',
        'TYPEOFFUEL', 'FUELTYPE', 'GROSSWEIGHT', 'NETWEIGHT', 'YEARREBUILT', 'PISTONDISPLACEMENT', 'MAXPOWER', 'OWNERSNAME',
        'OWNERSADDRESS', 'ENCUMBEREDTO', 'REMARKS', 'AIRCONTYPE', 'TRANSACTIONNO', 'ORNO', 'ORDATE', 'AMOUNT', 'NEWUSEDIMPORTEDCBU'];

    private const FIELDS = ['plate', 'make', 'model', 'color', 'year', 'body_type', 'registration_expiry'];

    /** Words of the printed OR/CR labels; a make, series or color never contains them. */
    private const LABEL_WORDS = ['PLATE', 'NUMBER', 'MAKE', 'BRAND', 'SERIES', 'MODEL', 'COLOR', 'COLOUR', 'YEAR', 'BODY', 'TYPE', 'VEHICLE',
        'ENGINE', 'CHASSIS', 'FILE', 'CATEGORY', 'CLASSIFICATION', 'PASSENGER', 'CAPACITY', 'FUEL', 'GROSS', 'WEIGHT', 'REBUILT',
        'PISTON', 'DISPLACEMENT', 'POWER', 'OWNER', 'OWNERS', 'NAME', 'ADDRESS', 'ENCUMBERED', 'REMARKS', 'AMOUNT', 'DATE',
        // Notes under labels: "(New/Used Imported CBU)", "(For Rebuilt MVs)", "(For electric vehicle)".
        'FOR', 'MVS', 'CBU', 'IMPORTED', 'USED', 'REBUILT'];

    /** Values of neighbouring cells (classification, fuel, empty marks) that are never a make, series or color. */
    private const OTHER_CELL_VALUES = ['PRIVATE', 'PVT', 'HIRE', 'GOVERNMENT', 'GOVT', 'DIPLOMATIC', 'GAS', 'GASOLINE', 'DIESEL', 'ELECTRIC', 'NA', 'NIA', 'NONE',
        // Body type / vehicle type cell words ("MOTORCYCLE WITHOUT SIDECAR"), never a make or series.
        'WITHOUT', 'SIDECAR', 'MOTORCYCLE', 'TRICYCLE', 'MOPED', 'SCOOTER'];

    /** Vehicle brands sold in the Philippines (fallback when the MAKE label is unreadable). */
    private const BRANDS = ['TOYOTA', 'HONDA', 'MITSUBISHI', 'NISSAN', 'SUZUKI', 'YAMAHA', 'KAWASAKI', 'HYUNDAI', 'KIA', 'FORD', 'ISUZU',
        'MAZDA', 'CHEVROLET', 'SUBARU', 'LEXUS', 'BMW', 'MERCEDES-BENZ', 'MERCEDES', 'AUDI', 'VOLKSWAGEN', 'PEUGEOT', 'GEELY', 'CHERY',
        'FOTON', 'BYD', 'GAC', 'CHANGAN', 'HAVAL', 'GWM', 'JEEP', 'VOLVO', 'PORSCHE', 'MINI', 'TATA', 'MAHINDRA', 'KYMCO', 'SYM', 'RUSI',
        'MOTORSTAR', 'KTM', 'DUCATI', 'VESPA', 'PIAGGIO', 'BAJAJ', 'TVS', 'BENELLI', 'CFMOTO', 'HARLEY-DAVIDSON', 'TRIUMPH', 'APRILIA'];

    /** A color value names at least one color. */
    private const COLOR_WORDS = ['BLACK', 'WHITE', 'RED', 'BLUE', 'SILVER', 'GRAY', 'GREY', 'GREEN', 'YELLOW', 'ORANGE', 'BROWN', 'BEIGE', 'GOLD',
        'MAROON', 'PURPLE', 'VIOLET', 'PINK', 'PEARL', 'BRONZE', 'CHAMPAGNE', 'TEAL', 'CREAM', 'NAVY', 'MAGENTA', 'CYAN', 'TURQUOISE', 'COPPER',
        'GUNMETAL', 'CHARCOAL', 'IVORY', 'BURGUNDY', 'LIME', 'AQUA', 'METALLIC', 'MATTE', 'MULTICOLOR', 'TWO', 'TONE'];

    /** Body-type words mapped to PASS vehicle type codes. */
    private const BODY_TYPES = [
        'motorcycle' => '/\b(?:MOTOR\s*CYCLE|MOTORCYCLE|SCOOTER|MOPED|M\/C)\b/',
        'pickup' => '/\bPICK\s*-?\s*UP\b/',
        'hatchback' => '/\bHATCH\s*BACK\b/',
        'suv' => '/\b(?:SUV|SPORT\s*UTILITY)\b/',
        'van' => '/\b(?:VAN|MPV)\b/',
        'sedan' => '/\bSEDAN\b/',
    ];

    /**
     * @return array{plate: ?string, make: ?string, model: ?string, color: ?string, year: ?int, body_type: ?string, type_code: ?string, registration_expiry: ?string, markers: array{or: bool, cr: bool, lto: bool}}
     */
    /** @param bool $sameLine only values printed on the label's own line (word positions handle the rest) */
    public function parse(string $text, bool $sameLine = false): array
    {
        $this->sameLine = $sameLine;
        $lines = array_values(array_filter(array_map(fn ($l) => trim(preg_replace('/\s+/', ' ', mb_strtoupper($l))), preg_split('/\R/', $text)), fn ($l) => $l !== ''));
        $upper = implode("\n", $lines);

        $plate = $this->plate($this->labeled($lines, 'plate'));
        $year = null;
        if (($value = $this->labeled($lines, 'year')) && preg_match('#(?<![\d/])(19[5-9]\d|20\d\d)(?![\d/])#', $value, $m)) $year = (int) $m[1];
        $body = $this->labeled($lines, 'body_type');

        return [
            'plate' => $plate,
            'make' => $this->words($this->labeled($lines, 'make')),
            'model' => $this->words($this->labeled($lines, 'model')),
            'color' => $this->words($this->labeled($lines, 'color')),
            'year' => $year,
            // Only a value that names a supported body type counts.
            'body_type' => $body && $this->typeCode($body) ? $this->words($body) : null,
            'type_code' => $body ? $this->typeCode($body) : null,
            'registration_expiry' => $this->validity($lines),
            'markers' => [
                'or' => (bool) preg_match('/OFFICIAL\s*RECEIPT/', $upper),
                // The CR heading is white on a dark band and its first letters are often
                // misread ("IRTIFICATE", "'CATE OF REGISTRATION"); two CR-only phrases also count.
                'cr' => (bool) preg_match('/[A-Z]*(?:TIFICATE|ICATE)\s*OF\s*REGIS|REGISTRANT\'?S?\s*SIGNAT|THIS\s*CERTIFICATE\s*IS\s*VALID/', $upper),
                'lto' => (bool) preg_match('/LAND\s*TRANSPORTATION/', $upper),
            ],
        ];
    }

    /** Normalized comparison key for plates: letters and digits only. */
    public static function plateKey(?string $plate): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper((string) $plate));
    }

    /**
     * Comparison key for an alphanumeric code (model/series): OCR look-alikes
     * are folded together, so "FU1SOMFZ" and "FU150MFZ" compare equal. Used
     * for comparing, never for the value shown or saved.
     */
    public static function codeKey(?string $value): string
    {
        return strtr(preg_replace('/[^A-Z0-9]/', '', mb_strtoupper((string) $value)), ['O' => '0', 'Q' => '0', 'D' => '0', 'S' => '5', 'I' => '1', 'L' => '1', 'B' => '8', 'Z' => '2', 'G' => '6']);
    }

    /** Normalized words for descriptive comparisons. */
    public static function wordsOf(?string $value): array
    {
        return preg_split('/[^A-Z0-9]+/', mb_strtoupper((string) $value), -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function category(?string $typeCode): ?string
    {
        if ($typeCode === null) return null;
        return $typeCode === 'motorcycle' ? 'motorcycle' : 'car';
    }

    /** Value printed after a label on the same line, or on the next line when the label stands alone. */
    private function labeled(array $lines, string $field): ?string
    {
        $label = '/(?:^|[^A-Z])' . self::LABELS[$field] . '\s*[:.#\-]*\s*/';
        foreach ($lines as $i => $line) {
            if (!preg_match($label, $line, $match, PREG_OFFSET_CAPTURE)) continue;
            $rest = trim(mb_substr($line, $match[0][1] + mb_strlen($match[0][0])));
            $rest = $this->cut($rest);
            if ($rest !== '') return $rest;
            $next = $lines[$i + 1] ?? '';
            // The next line is column-blind on a grid; it is used only without word positions.
            if (!$this->sameLine && $next !== '' && !$this->startsWithLabel($next)) return $this->cut($next);
            return null;
        }
        return null;
    }

    private function cut(string $value): string
    {
        $labels = array_merge(array_values(self::LABELS), self::STOP_LABELS);
        $pattern = '/\s(?:' . implode('|', $labels) . ')(?![A-Z])/';
        // A label at the very start means this line holds no value for the field.
        if (preg_match($pattern, ' ' . $value, $m, PREG_OFFSET_CAPTURE)) {
            $value = mb_substr($value, 0, max(0, $m[0][1] - 1));
        }
        return trim($value, " \t:.-#");
    }

    private function startsWithLabel(string $line): bool
    {
        $labels = array_merge(array_values(self::LABELS), self::STOP_LABELS);
        return (bool) preg_match('/^(?:' . implode('|', $labels) . ')(?![A-Z])/', $line);
    }

    /**
     * The first plate-shaped token after the label. Philippine plate shapes:
     * letters then digits (cars), digits then letters (motorcycles, e.g.
     * 555XRD). A peso amount such as "PHP 720.00" from the payment column of
     * the OR is never a plate.
     */
    private function plate(?string $value): ?string
    {
        if ($value === null) return null;
        preg_match_all('/\b(?:([A-Z]{1,3})\s*-?\s*(\d{3,5})|(\d{3,4})\s*-?\s*([A-Z]{2,3}))\b(?![.,]\d)/', $value, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            if (($m[1] ?? '') !== '') {
                if ($m[1] === 'PHP') continue;
                return $m[1] . ' ' . $m[2];
            }
            return $m[3] . ' ' . $m[4];
        }
        // OCR often reads a digit of a motorcycle plate as a look-alike letter
        // ("S55XRD", "SS5XRD" for 555XRD). Inside a labelled plate value only,
        // a digit group with at least one real digit is read as digits; the OR
        // and CR plates must still agree before anything is approved.
        if (preg_match('/\b([0-9SOIBZ]{3,4})\s*-?\s*([A-Z]{2,3})\b(?![.,]\d)/', $value, $m) && preg_match('/\d/', $m[1])) {
            return strtr($m[1], ['S' => '5', 'O' => '0', 'I' => '1', 'B' => '8', 'Z' => '2']) . ' ' . $m[2];
        }
        return null;
    }

    /**
     * Registration validity printed after "valid until" (or an expiry label),
     * which may wrap over the next lines: "valid until 05/2027 and due for
     * renewal on 05/08/2027-05/14/2027". The earliest end date is used, so a
     * registration is never treated as valid longer than printed.
     */
    private function validity(array $lines): ?string
    {
        $label = '/(?:^|[^A-Z])' . self::LABELS['registration_expiry'] . '/';
        foreach ($lines as $i => $line) {
            if (!preg_match($label, $line, $m, PREG_OFFSET_CAPTURE)) continue;
            $block = implode(' ', array_merge([mb_substr($line, $m[0][1])], array_slice($lines, $i + 1, 3)));
            $ends = [];
            // A month and year right after the label means the end of that month.
            if (preg_match('/^\D{0,40}?\b(\d{1,2})[\/.\-](\d{4})\b(?![\/.\-]\d)/', $block, $mm) && (int) $mm[1] >= 1 && (int) $mm[1] <= 12) {
                $ends[] = CarbonImmutable::create((int) $mm[2], (int) $mm[1], 1)->endOfMonth()->toDateString();
            }
            preg_match_all('/\b\d{1,2}[\/.\-]\d{1,2}[\/.\-]\d{4}\b|\b\d{4}[\/.\-]\d{1,2}[\/.\-]\d{1,2}\b|\b[A-Z]{3,9}\.?\s+\d{1,2},?\s+\d{4}\b/', $block, $dates);
            $full = array_values(array_filter(array_map(fn ($d) => $this->date($d), $dates[0])));
            if ($full) $ends[] = max($full);
            if ($ends) return min($ends);
        }
        return null;
    }

    /**
     * Fields from every read of one document (VehicleDocumentReader), each
     * value chosen by agreement between reads. Grid reads pair a value with
     * the label above it or beside it using word positions; plain text reads
     * use the line parser. A field no read can find stays null.
     *
     * @return array same shape as parse(), plus "agreement" (reads that found each value)
     */
    public function parseDocument(array $document): array
    {
        $reads = $document['reads'] ?? [$document];
        $candidates = [];
        $cells = [];
        $markers = ['or' => false, 'cr' => false, 'lto' => false];
        foreach ($reads as $read) {
            // A targeted re-read of one cell (VehicleDocumentReader) gives that field only.
            if (isset($read['field'])) {
                // The crop may include the next row's label; each line is judged on its own.
                foreach (preg_split('/\R/', mb_strtoupper((string) ($read['text'] ?? ''))) as $line) {
                    $value = $this->gridValue($read['field'], trim($line));
                    if ($value !== null) { $cells[$read['field']][] = [$value, (float) ($read['confidence'] ?? 0)]; break; }
                }
                continue;
            }
            $text = $this->parse((string) ($read['text'] ?? ''), sameLine: isset($read['words'][0]['box']));
            foreach ($text['markers'] as $key => $found) $markers[$key] = $markers[$key] || $found;
            $grid = isset($read['words'][0]['box']) ? $this->parseGrid($read['words']) : [];
            foreach (self::FIELDS as $field) {
                // Position-paired values are preferred over line-parsed ones from the same read.
                $value = $grid[$field]['value'] ?? $text[$field];
                $confidence = $grid[$field]['confidence'] ?? (float) ($read['confidence'] ?? 0);
                // A line-parsed make, series or color must be made of legible words too: the
                // background pattern read as "ee to." beside SERIES is noise, not a value.
                if (!isset($grid[$field]) && $value !== null && isset($read['words'][0]['box']) && in_array($field, ['make', 'model', 'color'], true)) {
                    $confidence = self::wordConfidence($read['words'], (string) $value);
                    if ($confidence < 50) continue;
                }
                if ($value !== null && $value !== '' && $this->plausible($field, (string) $value)) $candidates[$field][] = [$value, $confidence];
            }
        }
        // Cross-read pairing: all reads share one coordinate system, so a label
        // found by one read can pair with a value read by another. Used only for
        // fields that no single read found, so it never inflates agreement.
        $geometric = array_values(array_filter($reads, fn ($r) => !isset($r['field']) && isset($r['words'][0]['box'])));
        if (count($geometric) > 1) {
            foreach ($this->parseGrid(self::mergeWords($geometric)) as $field => $found) {
                if (empty($candidates[$field]) && $this->plausible($field, (string) $found['value'])) $candidates[$field][] = [$found['value'], $found['confidence']];
            }
        }
        // The make's small label is sometimes unreadable while the brand itself is
        // clear. A known brand read confidently counts as the make, but only when it
        // is the one brand on the document (it is still checked against the form).
        if (empty($candidates['make']) && empty($cells['make'])) {
            $brands = [];
            foreach ($geometric ?: $reads as $read) foreach ($read['words'] ?? [] as $word) {
                $text = preg_replace('/[^A-Z\-]/', '', mb_strtoupper((string) $word['text']));
                if ($word['confidence'] >= 80 && in_array($text, self::BRANDS, true)) $brands[$text] = max($brands[$text] ?? 0, (float) $word['confidence']);
            }
            if (count($brands) === 1) $candidates['make'][] = [array_key_first($brands), reset($brands)];
        }
        // A cell re-read is a fallback: it counts only when its two reads agree
        // or another read found the same value, never as a lone guess.
        $keyOf = fn (string $field, $value) => $field === 'plate' ? self::plateKey($value) : (string) $value;
        foreach ($cells as $field => $values) {
            $seen = array_count_values(array_map(fn ($v) => $keyOf($field, $v[0]), $values));
            $elsewhere = array_map(fn ($v) => $keyOf($field, $v[0]), $candidates[$field] ?? []);
            foreach ($values as $v) {
                if ($seen[$keyOf($field, $v[0])] >= 2 || in_array($keyOf($field, $v[0]), $elsewhere, true)) $candidates[$field][] = $v;
            }
        }
        $result = ['agreement' => []];
        foreach (self::FIELDS as $field) {
            $votes = [];
            foreach ($candidates[$field] ?? [] as $order => [$value, $confidence]) {
                $key = match ($field) { 'plate' => self::plateKey($value), 'model' => self::codeKey($value), default => (string) $value };
                $votes[$key] ??= ['value' => $value, 'count' => 0, 'confidence' => 0, 'first' => $order];
                // Of look-alike readings, show the most confident one.
                if ($confidence > $votes[$key]['confidence']) $votes[$key]['value'] = $value;
                $votes[$key]['count']++;
                $votes[$key]['confidence'] = max($votes[$key]['confidence'], $confidence);
            }
            // Most reads agreeing first, then the most confident reading.
            uasort($votes, fn ($a, $b) => [$b['count'], $b['confidence'], $a['first']] <=> [$a['count'], $a['confidence'], $b['first']]);
            $winner = $votes ? reset($votes) : null;
            $result[$field] = $winner['value'] ?? null;
            $result['agreement'][$field] = $winner['count'] ?? 0;
        }
        if ($result['year'] !== null) $result['year'] = (int) $result['year'];
        $result['type_code'] = $result['body_type'] ? $this->typeCode($result['body_type']) : null;
        // A CR's footer mentions the "Official Receipt"; it is still a CR, not an OR.
        $markers['or'] = $markers['or'] && !$markers['cr'];
        $result['markers'] = $markers;
        // Fields whose printed label was located: lets the caller tell "label found,
        // value unreadable" apart from "this part of the document was not found".
        $found = [];
        foreach ($geometric as $read) foreach ($this->findLabels($read['words'])[1] as $label) if ($label['field']) $found[$label['field']] = true;
        $result['labels_found'] = array_keys($found);
        return $result;
    }

    /** Mean OCR confidence of the words that make up a value; a word not found counts as 0. */
    private static function wordConfidence(array $words, string $value): float
    {
        $key = fn ($t) => preg_replace('/[^A-Z0-9]/', '', mb_strtoupper((string) $t));
        $tokens = array_values(array_filter(array_map($key, preg_split('/\s+/', $value))));
        if (!$tokens) return 0.0;
        $best = array_fill_keys($tokens, 0.0);
        foreach ($words as $word) {
            $k = $key($word['text']);
            if (isset($best[$k])) $best[$k] = max($best[$k], (float) $word['confidence']);
        }
        return array_sum(array_map(fn ($t) => $best[$t], $tokens)) / count($tokens);
    }

    /**
     * One pool of words from several reads of the same upright image: where
     * boxes overlap, the more confident reading is kept; words are regrouped
     * into lines by position.
     */
    private static function mergeWords(array $reads): array
    {
        $all = [];
        foreach ($reads as $read) foreach ($read['words'] as $word) if (isset($word['box'])) $all[] = $word;
        usort($all, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);
        $kept = [];
        foreach ($all as $word) {
            [$x, $y, $w, $h] = $word['box'];
            foreach ($kept as $other) {
                [$ox, $oy, $ow, $oh] = $other['box'];
                $ix = max(0, min($x + $w, $ox + $ow) - max($x, $ox)); $iy = max(0, min($y + $h, $oy + $oh) - max($y, $oy));
                if ($ix * $iy > .5 * min($w * $h, $ow * $oh)) continue 2;
            }
            $kept[] = $word;
        }
        usort($kept, fn ($a, $b) => ($a['box'][1] + $a['box'][3] / 2) <=> ($b['box'][1] + $b['box'][3] / 2));
        $lines = [];
        foreach ($kept as $word) {
            $center = $word['box'][1] + $word['box'][3] / 2;
            $last = count($lines) - 1;
            if ($last >= 0 && abs($center - $lines[$last]['center']) <= .5 * max($word['box'][3], $lines[$last]['height'])) {
                $lines[$last]['words'][] = $word;
            } else {
                $lines[] = ['center' => $center, 'height' => $word['box'][3], 'words' => [$word]];
            }
        }
        $merged = [];
        foreach ($lines as $i => $line) {
            usort($line['words'], fn ($a, $b) => $a['box'][0] <=> $b['box'][0]);
            foreach ($line['words'] as $word) $merged[] = ['line' => "merged-{$i}"] + $word;
        }
        return $merged;
    }

    /**
     * Cells to read again on their own: fields with no reading yet whose label
     * was located by some read. Boxes are in the shared upright-image
     * coordinates: [left, top, width, height].
     *
     * @return array<string, array{0: int, 1: int, 2: int, 3: int}>
     */
    public function fieldCells(array $document): array
    {
        $parsed = $this->parseDocument($document);
        $cells = [];
        foreach ($document['reads'] ?? [] as $read) {
            if (isset($read['field']) || !isset($read['words'][0]['box'])) continue;
            [, $labels] = $this->findLabels($read['words']);
            foreach ($labels as $label) {
                $field = $label['field'];
                if (!in_array($field, ['plate', 'make', 'model', 'color', 'year'], true) || $parsed[$field] !== null || isset($cells[$field])) continue;
                [$l, , , $b] = $label['box'];
                $h = $this->labelHeight($label);
                $right = $this->cellRight($label, $labels);
                $cells[$field] = [max(0, (int) ($l - $h)), (int) ($b + $h * .1), (int) ($right - $l + $h), (int) (3.2 * $h)];
            }
        }
        return $cells;
    }

    /**
     * Pair labels with values by position: the value is to the right of its
     * label on the same line, or below it within the label's column (the CR
     * prints small labels above each value cell).
     *
     * @param array<int, array{text: string, confidence: float, line: string, box: array{0: int, 1: int, 2: int, 3: int}}> $words
     */
    private function parseGrid(array $words): array
    {
        [$lines, $labels] = $this->findLabels($words);
        $isLabelWord = function (int $li, int $wi) use ($labels): bool {
            foreach ($labels as $label) if ($label['line'] === $li && $wi >= $label['from'] && $wi < $label['to']) return true;
            return false;
        };

        $found = [];
        foreach ($labels as $label) {
            $field = $label['field'];
            // Background pattern and borders read as low-confidence fragments. A plate or
            // year must also match a strict format, so a fainter reading may be used for those.
            $minimum = in_array($field, ['plate', 'year'], true) ? 25 : 50;
            $legible = fn (array $word) => $word['confidence'] >= $minimum;
            // A more specific label wins ("BODY TYPE" over "VEHICLE TYPE").
            if (!$field || (isset($found[$field]) && $found[$field]['rank'] <= $label['rank'])) continue;
            [$l, $t, $r, $b] = $label['box'];
            $h = $this->labelHeight($label);
            $right = $this->cellRight($label, $labels);
            $options = [];
            // 1. Same line, right of the label, until another label, a column
            //    separator or a column-sized gap (the OR's payment column).
            $same = [];
            $edge = $r;
            foreach ($lines[$label['line']] as $wi => $word) {
                if ($wi < $label['to']) continue;
                if ($isLabelWord($label['line'], $wi) || str_contains($word['text'], '|') || $word['box'][0] - $edge > 3 * $h || $word['box'][0] > $right) break;
                $edge = $word['box'][0] + $word['box'][2];
                if ($legible($word)) $same[] = $word;
            }
            if ($same) $options[] = $same;
            // 2. Below, inside the label's column, nearest lines first, ending
            //    at the next row of labels (the cell below belongs to another field).
            $inColumn = function (array $word) use ($l, $b, $h, $right): bool {
                [$wl, $wt, $ww] = $word['box'];
                $center = $wl + $ww / 2;
                return $wt > $b - $h * .2 && $wt <= $b + 3.5 * $h && $center >= $l - 2 * $h && $center <= $right;
            };
            $nextLabelRow = INF;
            foreach ($labels as $other) {
                // A note printed right under the label ("(New/Used Imported CBU)") belongs to the same cell.
                if ($other !== $label && $other['box'][1] > $b + $h * .8 && $inColumn(['box' => [$other['box'][0], $other['box'][1], $other['box'][2] - $other['box'][0]]])) {
                    $nextLabelRow = min($nextLabelRow, $other['box'][1]);
                }
            }
            $below = [];
            foreach ($lines as $li => $line) {
                $cell = [];
                foreach ($line as $wi => $word) {
                    if (!$inColumn($word) || $word['box'][1] >= $nextLabelRow - $h * .3 || $isLabelWord($li, $wi)) continue;
                    if ($legible($word)) $cell[] = $word;
                }
                if ($cell) $below[min(array_map(fn ($w) => $w['box'][1], $cell))] = $cell;
            }
            ksort($below);
            array_push($options, ...array_values($below));
            foreach ($options as $option) {
                $value = $this->gridValue($field, mb_strtoupper(trim(implode(' ', array_column($option, 'text')))));
                if ($value !== null) {
                    $found[$field] = ['value' => $value, 'rank' => $label['rank'], 'confidence' => array_sum(array_column($option, 'confidence')) / count($option)];
                    break;
                }
            }
        }
        return array_map(fn ($f) => ['value' => $f['value'], 'confidence' => $f['confidence']], $found);
    }

    /**
     * Lines of words and every label occurrence (field labels and the other
     * printed labels that bound neighbouring cells), each with its box.
     *
     * @return array{0: array<int, array>, 1: array<int, array{field: ?string, rank: int, line: int, from: int, to: int, box: array{0: int, 1: int, 2: int, 3: int}}>}
     */
    private function findLabels(array $words): array
    {
        $lines = [];
        foreach ($words as $word) if (isset($word['box'])) $lines[$word['line']][] = $word;
        $lines = array_values($lines);
        $labels = [];
        foreach ($lines as $li => $line) {
            for ($i = 0; $i < count($line); $i++) {
                for ($n = 3; $n >= 1; $n--) {
                    if ($i + $n > count($line)) continue;
                    $span = array_slice($line, $i, $n);
                    // Every word of a label has letters, so a value such as "2024" is never absorbed into it.
                    if (count(array_filter($span, fn ($w) => preg_match('/[A-Za-z]{2}/', $w['text']))) !== $n) continue;
                    $text = implode(' ', array_column($span, 'text'));
                    $field = null; $rank = null;
                    foreach (self::GRID_LABELS as $f => $names) if (($rank = self::labelMatch($text, $names)) !== null) { $field = $f; break; }
                    if ($field === 'plate' && self::GRID_LABELS['plate'][$rank] === 'PLATE' && $i !== 0) { $field = null; $rank = null; }
                    // A bare "MODEL" right after "YEAR" is the year label ("YEAR MODEL"), not the series.
                    if ($field === 'model' && self::GRID_LABELS['model'][$rank] === 'MODEL' && self::followsYear($span[0], $words)) continue;
                    $stop = !$field && self::labelMatch($text, self::GRID_STOPS) !== null;
                    if (!$field && !$stop) continue;
                    $boxes = array_column($span, 'box');
                    $l = min(array_column($boxes, 0)); $t = min(array_column($boxes, 1));
                    $r = max(array_map(fn ($b) => $b[0] + $b[2], $boxes)); $b = max(array_map(fn ($b) => $b[1] + $b[3], $boxes));
                    $labels[] = ['field' => $field, 'rank' => $rank ?? 99, 'line' => $li, 'from' => $i, 'to' => $i + $n, 'box' => [$l, $t, $r, $b]];
                    $i += $n - 1;
                    continue 2;
                }
            }
        }
        return [$lines, $labels];
    }

    /** Whether a "YEAR" word sits immediately left of this word on the same line. */
    private static function followsYear(array $word, array $words): bool
    {
        [$l, $t, , $h] = $word['box'];
        foreach ($words as $other) {
            if (!isset($other['box']) || preg_replace('/[^A-Z]/', '', mb_strtoupper($other['text'])) !== 'YEAR') continue;
            [$ol, $ot, $ow, $oh] = $other['box'];
            if ($ol + $ow <= $l + $h && $l - ($ol + $ow) < 3 * $h && abs(($ot + $oh / 2) - ($t + $h / 2)) < max($h, $oh) * .7) return true;
        }
        return false;
    }

    /** Index of the matching label name, or null; small print is often misread by a letter ("IMAKEBRAND"). */
    private static function labelMatch(string $text, array $labels): ?int
    {
        $k = preg_replace('/[^A-Z]/', '', strtr(mb_strtoupper($text), ['0' => 'O']));
        foreach ($labels as $rank => $label) {
            if ($k === $label) return $rank;
            $len = strlen($label);
            if ($len >= 6 && levenshtein($k, $label) <= ($len >= 9 ? 2 : 1)) return $rank;
        }
        return null;
    }

    private function labelHeight(array $label): float
    {
        return max(8, $label['box'][3] - $label['box'][1]);
    }

    /** Right edge of a label's cell: the next label to its right in the same row, or a wide default. */
    private function cellRight(array $label, array $labels): float
    {
        [, $t, $r, $b] = $label['box'];
        $h = $this->labelHeight($label);
        $right = $r + 14 * $h;
        foreach ($labels as $other) {
            [$ol, $ot, , $ob] = $other['box'];
            // Labels of one row can differ in type size; compare their vertical centres.
            if ($other !== $label && $ol > $r && abs(($ot + $ob) / 2 - ($t + $b) / 2) < 1.5 * $h) $right = min($right, $ol - $h / 2);
        }
        return $right;
    }

    private function gridValue(string $field, string $raw): ?string
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw), " \t:.-#|");
        if ($raw === '') return null;
        $value = match ($field) {
            'plate' => $this->plate($raw),
            'year' => preg_match('#(?<![\d/])(19[5-9]\d|20\d\d)(?![\d/])#', $raw, $m) ? $m[1] : null,
            'body_type' => $this->typeCode($raw) ? $this->words($raw) : null,
            // Make, series and color are words with at least two letters, not a lone symbol.
            default => preg_match('/[A-Z]{2}/', $raw) ? $this->words($raw) : null,
        };
        return $value !== null && $this->plausible($field, $value) ? $value : null;
    }

    /**
     * Rejects values that belong to another cell: misread labels ("VERAND"
     * for "BRAND", "ROSS WEIGHT"), the classification or fuel printed next to
     * the make, and a "color" without any color word.
     */
    private function plausible(string $field, string $value): bool
    {
        if (!in_array($field, ['make', 'model', 'color'], true)) return true;
        $words = self::wordsOf($value);
        if (!$words) return false;
        $letters = implode('', $words);
        foreach (array_merge(array_merge(...array_values(self::GRID_LABELS)), self::GRID_STOPS) as $label) {
            if ($letters === $label || (strlen($label) >= 5 && levenshtein($letters, $label) <= 2)) return false;
        }
        $labelWords = array_merge(self::LABEL_WORDS, array_merge(...array_values(self::GRID_LABELS)), self::GRID_STOPS);
        foreach ($words as $word) {
            if (in_array($word, self::OTHER_CELL_VALUES, true)) return false;
            foreach (self::LABEL_WORDS as $labelWord) {
                if ($word === $labelWord || (strlen($labelWord) >= 5 && levenshtein($word, $labelWord) <= 1)) return false;
            }
            // A label cut off at the cell edge ("CLASSIFICA" from CLASSIFICATION) is not a value.
            foreach ($labelWords as $labelWord) {
                if (strlen($word) >= 5 && strlen($labelWord) > strlen($word) && str_starts_with($labelWord, $word)) return false;
            }
        }
        // A make is a brand name, never a two-letter fragment such as "IZ".
        if ($field === 'make' && strlen(preg_replace('/[^A-Z]/', '', $letters)) < 3) return false;
        return $field !== 'color' || (bool) array_intersect($words, self::COLOR_WORDS);
    }

    private function words(?string $value): ?string
    {
        if ($value === null) return null;
        $value = trim(preg_replace('/[^A-Z0-9 .\-\/]/', '', $value), ' .');
        return $value === '' ? null : mb_substr($value, 0, 100);
    }

    private function typeCode(string $body): ?string
    {
        foreach (self::BODY_TYPES as $code => $pattern) {
            if (preg_match($pattern, $body)) return $code;
        }
        return null;
    }

    private function date(?string $value): ?string
    {
        if ($value === null) return null;
        $months = 'JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUNE?|JULY?|AUG(?:UST)?|SEP(?:T(?:EMBER)?)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?';
        try {
            if (preg_match('/\b(\d{4})[\/.\-](\d{1,2})[\/.\-](\d{1,2})\b/', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return CarbonImmutable::create((int) $m[1], (int) $m[2], (int) $m[3])->toDateString();
            }
            if (preg_match('/\b(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})\b/', $value, $m) && checkdate((int) $m[1], (int) $m[2], (int) $m[3])) {
                // Philippine documents print month/day/year.
                return CarbonImmutable::create((int) $m[3], (int) $m[1], (int) $m[2])->toDateString();
            }
            if (preg_match('/\b(' . $months . ')\.?\s+(\d{1,2}),?\s+(\d{4})\b/', $value, $m)) {
                return CarbonImmutable::parse("{$m[1]} {$m[2]} {$m[3]}")->toDateString();
            }
        } catch (Throwable) {
            return null;
        }
        return null;
    }
}
