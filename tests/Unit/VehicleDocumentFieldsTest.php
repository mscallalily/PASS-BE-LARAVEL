<?php

namespace Tests\Unit;

use App\Services\Vehicles\VehicleDocumentFields;
use PHPUnit\Framework\TestCase;

class VehicleDocumentFieldsTest extends TestCase
{
    private function parser(): VehicleDocumentFields
    {
        return new VehicleDocumentFields;
    }

    /** OCR words laid out on a grid: [text, left, top] with a fixed word height. */
    private function words(array $rows, float $confidence = 95.0): array
    {
        $words = [];
        foreach ($rows as $line => $row) foreach ($row as [$text, $left, $top]) {
            $words[] = ['text' => $text, 'confidence' => $confidence, 'line' => "1-1-{$line}", 'box' => [$left, $top, 12 * strlen($text), 20]];
        }
        return $words;
    }

    private function read(array $words): array
    {
        $lines = [];
        foreach ($words as $w) $lines[$w['line']][] = $w['text'];
        return ['text' => implode("\n", array_map(fn ($l) => implode(' ', $l), $lines)), 'words' => $words, 'confidence' => 80.0];
    }

    public function test_a_peso_amount_in_the_payment_column_is_never_the_plate(): void
    {
        // Real OR: the payment breakdown column merges into the plate line.
        $or = "OFFICIAL RECEIPT\nPlate No: 555XRD | wee PHP 729.00\nColor: BLACK";
        $this->assertSame('555 XRD', $this->parser()->parse($or)['plate']);
        $this->assertNull($this->parser()->parse("OFFICIAL RECEIPT\nPlate No: PHP 720.00")['plate']);
        $this->assertSame('ABC 1234', $this->parser()->parse("PLATE NO. ABC-1234 MV FILE 1301")['plate']);
    }

    public function test_registration_validity_wrapping_over_lines_uses_the_earliest_end_date(): void
    {
        $or = "OFFICIAL RECEIPT\nThis payment is valid until)\n05/2027 and due for i\nrenewal on 05/08/2027- {\n05/14/2027. |";
        $this->assertSame('2027-05-14', $this->parser()->parse($or)['registration_expiry']);
        $this->assertSame('2026-11-30', $this->parser()->parse("VALID UNTIL 11/2026")['registration_expiry']);
        $this->assertNull($this->parser()->parse("OFFICIAL RECEIPT\nDate: 08/14/2024")['registration_expiry']);
    }

    public function test_classification_is_not_a_body_type_and_vehicle_type_is(): void
    {
        $parsed = $this->parser()->parse("Classification: Private - (PVT) H pen bal PHP 40.50\nVehicle Type: Motorcycle Without Sidecar");
        $this->assertSame('motorcycle', $parsed['type_code']);
        $this->assertStringStartsWith('MOTORCYCLE', $parsed['body_type']);
        $this->assertNull($this->parser()->parse('CLASSIFICATION: PRIVATE')['body_type']);
    }

    public function test_a_cr_mentioning_the_official_receipt_is_not_an_or(): void
    {
        $cr = $this->parser()->parseDocument(['text' => "CERTIFICATE OF REGISTRATION\naccompanied by Official Receipt as proof of payment"]);
        $this->assertTrue($cr['markers']['cr']);
        $this->assertFalse($cr['markers']['or']);
        $this->assertTrue($this->parser()->parseDocument(['text' => "OFFICIAL RECEIPT\nPLATE NO. ABC 1234"])['markers']['or']);
    }

    public function test_grid_values_are_paired_with_the_label_above_them_in_the_same_column(): void
    {
        // CR grid: a row of labels, then a row of values; four columns.
        $words = $this->words([
            [['PLATE', 100, 100], ['NO.', 170, 100], ['ENGINE', 500, 100], ['NO.', 580, 100], ['MAKE/BRAND', 900, 100]],
            [['555XRD', 110, 140], ['CGA1-779901', 510, 140], ['SUZUKI', 910, 140]],
            [['YEAR', 100, 300], ['MODEL', 160, 300], ['SERIES', 500, 300]],
            [['(New/Used', 100, 330], ['Imported', 210, 330], ['CBU)', 320, 330]],
            [['2024', 110, 370], ['FU150MFZ', 510, 370]],
        ]);
        $parsed = $this->parser()->parseDocument(['reads' => [$this->read($words)]]);
        $this->assertSame('555 XRD', $parsed['plate']);
        $this->assertSame('SUZUKI', $parsed['make']);
        $this->assertSame('FU150MFZ', $parsed['model']);
        $this->assertSame(2024, $parsed['year']);
        // The engine number in the next column is never taken as the plate or make.
        $this->assertStringNotContainsString('CGA1', json_encode($parsed));
    }

    public function test_a_value_on_the_label_line_stops_at_a_column_gap(): void
    {
        // OR: "Vehicle Type: Motorcycle Without Sidecar" then, far right, the payment column.
        $words = $this->words([[['Vehicle', 100, 100], ['Type:', 200, 100], ['Motorcycle', 280, 100], ['Without', 410, 100], ['Sidecar', 510, 100], ['Science', 1100, 100], ['Tax', 1200, 100]]]);
        $parsed = $this->parser()->parseDocument(['reads' => [$this->read($words)]]);
        $this->assertSame('MOTORCYCLE WITHOUT SIDECAR', $parsed['body_type']);
        $this->assertSame('motorcycle', $parsed['type_code']);
    }

    public function test_misread_labels_noise_and_missing_cells_are_never_values(): void
    {
        $words = $this->words([
            [['IMAKE/BRAND', 900, 100], ['COLOR', 1300, 100]],
            [['VERAND', 910, 140]],
        ]);
        $parsed = $this->parser()->parseDocument(['reads' => [$this->read($words)]]);
        $this->assertNull($parsed['make']);   // "VERAND" is a misread label, not a make
        $this->assertNull($parsed['color']);  // nothing below COLOR: missing, not guessed
        // A neighbouring label cut off at the cell edge is not a make.
        $cut = $this->words([[['MAKE/BRAND', 900, 100]], [['CLASSIFICA', 910, 140], ['NUM', 1050, 140]]]);
        $this->assertNull($this->parser()->parseDocument(['reads' => [$this->read($cut)]])['make']);
        // Low-confidence fragments (background pattern) are ignored.
        $noise = $this->words([[['MAKE', 100, 100]], [['eS', 110, 140]]], 30.0);
        $this->assertNull($this->parser()->parseDocument(['reads' => [$this->read($noise)]])['make']);
    }

    public function test_plate_label_without_its_no_and_look_alike_digits_in_the_plate(): void
    {
        // Real CR: "PLATE" read without "NO.", and the plate read as "S55XRD".
        $words = $this->words([[['PLATE', 16, 175]], [['S55XRD', 181, 210]]], 49.0);
        $this->assertSame('555 XRD', $this->parser()->parseDocument(['reads' => [$this->read($words)]])['plate']);
        // No real digit at all: not a plate.
        $words = $this->words([[['PLATE', 16, 175]], [['SSSXRD', 181, 210]]]);
        $this->assertNull($this->parser()->parseDocument(['reads' => [$this->read($words)]])['plate']);
        // "plate" in the middle of a line (the OR's "Cost of plate") is not a plate label.
        $words = $this->words([[['Cost', 16, 175], ['of', 80, 175], ['plate', 120, 175]], [['ABC123', 120, 210]]]);
        $this->assertNull($this->parser()->parseDocument(['reads' => [$this->read($words)]])['plate']);
    }

    public function test_a_label_from_one_read_pairs_with_a_value_from_another(): void
    {
        // Same upright image: one pass read only the label, another only the value.
        $labelOnly = $this->read($this->words([[['SERIES', 500, 590]]]));
        $valueOnly = $this->read($this->words([[['FU150MFZ', 520, 630]], [['FILLER', 1500, 2000]]]));
        $parsed = $this->parser()->parseDocument(['reads' => [$labelOnly, $valueOnly]]);
        $this->assertSame('FU150MFZ', $parsed['model']);
        $this->assertSame(1, $parsed['agreement']['model']);
        $this->assertContains('model', $parsed['labels_found']);
    }

    public function test_year_model_is_never_a_series_label_and_owner_name_is_never_a_value(): void
    {
        // "MODEL" separated from "YEAR" must not become the series label.
        $words = $this->words([
            [['YEAR', 50, 760], ['MODEL', 120, 760]],
            [['2024', 60, 800]],
            [["OWNER'S", 50, 860], ['NAME', 160, 860]],
            [['REY', 50, 900], ['SARUDA', 120, 900], ['ARCA', 230, 900]],
        ]);
        $parsed = $this->parser()->parseDocument(['reads' => [$this->read($words)]]);
        $this->assertSame(2024, $parsed['year']);
        $this->assertNull($parsed['model']);
    }

    public function test_series_codes_keep_hyphens_and_digits(): void
    {
        $words = $this->words([[['MODEL/SERIES', 100, 100]], [['NMAX-155', 110, 140]]]);
        $this->assertSame('NMAX-155', $this->parser()->parseDocument(['reads' => [$this->read($words)]])['model']);
        $this->assertSame(VehicleDocumentFields::codeKey('FU150MFZ'), VehicleDocumentFields::codeKey('FU1SOMFZ'));
        $this->assertNotSame(VehicleDocumentFields::codeKey('FU150MFZ'), VehicleDocumentFields::codeKey('FU125MFZ'));
    }

    public function test_a_single_clear_brand_is_the_make_when_its_label_is_unreadable(): void
    {
        $one = $this->read($this->words([[['SUZUKI', 1536, 435]], [['BLACK', 800, 435]]], 96.0));
        $this->assertSame('SUZUKI', $this->parser()->parseDocument(['reads' => [$one, $one]])['make']);
        // Two different brands on the document: ambiguous, so nothing is guessed.
        $two = $this->read($this->words([[['SUZUKI', 1536, 435]], [['HONDA', 800, 600]]], 96.0));
        $this->assertNull($this->parser()->parseDocument(['reads' => [$two, $two]])['make']);
    }

    public function test_background_noise_beside_a_label_is_not_a_value_and_another_read_supplies_it(): void
    {
        // Real 166XAB CR: one read sees SERIES with the shaded cell read as "ee to." (43% / 13%);
        // a locally thresholded read misses the faint label but reads the series below it.
        $noisy = $this->read([
            ['text' => 'SERIES', 'confidence' => 94.0, 'line' => '1-1-3', 'box' => [445, 428, 96, 44]],
            ['text' => 'ee', 'confidence' => 43.0, 'line' => '1-1-3', 'box' => [746, 440, 80, 21]],
            ['text' => 'to.', 'confidence' => 13.0, 'line' => '1-1-3', 'box' => [884, 433, 55, 27]],
        ]);
        $local = $this->read([
            ['text' => 'UB125NM', 'confidence' => 89.0, 'line' => '2-1-1', 'box' => [575, 493, 173, 28]],
            ['text' => 'FILLER', 'confidence' => 90.0, 'line' => '2-1-9', 'box' => [1500, 2000, 80, 20]],
        ]);
        $this->assertNull($this->parser()->parseDocument(['reads' => [$noisy]])['model']);
        $this->assertSame('UB125NM', $this->parser()->parseDocument(['reads' => [$noisy, $local]])['model']);
    }

    public function test_a_year_inside_a_validity_date_is_not_the_year_model(): void
    {
        // Real 166XAB OR: the year cell is read together with "valid until 06/2027".
        $this->assertNull($this->parser()->parse("Year Model:\nThis payment is valid until 06/2027")['year']);
        $this->assertSame(2023, $this->parser()->parse('Year Model: 2023')['year']);
        $words = $this->words([[['YEAR', 100, 100], ['MODEL', 170, 100]], [['06/2027', 110, 140], ['due', 300, 140]]]);
        $this->assertNull($this->parser()->parseDocument(['reads' => [$this->read($words)]])['year']);
    }

    public function test_reads_vote_and_a_tie_goes_to_the_more_confident_reading(): void
    {
        $label = [['MAKE/BRAND', 100, 100]];
        $a = $this->words([$label, [['SUZUKI', 110, 140]]], 96.0);
        $b = $this->words([$label, [['SUZUK1', 110, 140]]], 60.0);
        $parsed = $this->parser()->parseDocument(['reads' => [$this->read($b), $this->read($a)]]);
        $this->assertSame('SUZUKI', $parsed['make']);
        $this->assertSame(1, $parsed['agreement']['make']);
        $parsed = $this->parser()->parseDocument(['reads' => [$this->read($a), $this->read($a), $this->read($b)]]);
        $this->assertSame(2, $parsed['agreement']['make']);
    }
}
