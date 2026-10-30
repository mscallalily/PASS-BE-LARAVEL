<?php

namespace Tests\Feature\Parking;

use App\Services\LicenseVerification\LicenseDocumentReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** Upload-first license autofill: field-level readability, never a whole-image gate. */
class LicenseAutofillTest extends ParkingTestCase
{
    private const CARD = "REPUBLIC OF THE PHILIPPINES\nDEPARTMENT OF TRANSPORTATION\nLAND TRANSPORTATION OFFICE\nDRIVER'S LICENSE\nLast Name. First Name. Middle Name\nDELA CRUZ, JUAN\nNationality Sex Date of Birth\nPHL M 1995/05/14\nLicense Na. Expirzuon Date Agency Code\nN01-23-456789 2030/05/14 N01\nBlood Type Eyes Color Conditions";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $car = $this->campusVehicle();
        $car['user']->roles()->attach(DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($car['user']);
    }

    /** Whole-card OCR as LicenseDocumentReader returns it; region reads are unavailable. */
    private function reads(?string $text, array $over = [], array $scores = []): void
    {
        $words = [];
        foreach (explode("\n", (string) $text) as $i => $line) foreach (array_filter(explode(' ', $line), 'strlen') as $word) {
            $words[] = ['text' => $word, 'line' => 'line-'.$i, 'confidence' => $scores[$word] ?? 95.0];
        }
        $document = $over + ['kind' => 'image', 'engine_available' => true, 'engine_error' => null, 'text' => (string) $text,
            'words' => $words, 'confidence' => 70.0, 'width' => 1700, 'height' => 1071, 'sharpness' => 150.0];
        $reader = $this->mock(LicenseDocumentReader::class);
        $reader->shouldReceive('read')->once()->andReturn($document);
        $reader->shouldReceive('readTextImage')->zeroOrMoreTimes()->andReturn(['engine_available' => false]);
    }

    private function autofill()
    {
        return $this->post('/api/documents/autofill', ['document_type' => 'driver_license', 'document' => UploadedFile::fake()->image('license.jpg', 1700, 1071)], ['Accept' => 'application/json'])->assertOk();
    }

    public function test_readable_card_fills_both_fields_even_with_a_low_whole_image_mean(): void
    {
        // Mean 70% (photo, seal, hologram) used to send every field to "retake".
        $this->reads(self::CARD);
        $this->autofill()->assertJsonPath('status', 'ok')
            ->assertJsonPath('fields.license_number', 'N01-23-456789')
            ->assertJsonPath('fields.expiry_date', '2030-05-14')
            ->assertJsonPath('flags', [])->assertJsonPath('issues', [])->assertJsonMissingPath('text');
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_partial_read_keeps_the_readable_field_and_names_the_missing_one(): void
    {
        $this->reads(str_replace('N01-23-456789 ', '', self::CARD));
        $response = $this->autofill()->assertJsonPath('status', 'partial')
            ->assertJsonPath('fields.expiry_date', '2030-05-14')
            ->assertJsonPath('flags.license_number', 'missing')->assertJsonMissingPath('flags.expiry_date');
        $this->assertStringContainsString('license number could not be read', strtolower(implode(' ', $response->json('issues'))));
        $this->assertArrayNotHasKey('license_number', $response->json('fields'));
    }

    public function test_weakly_read_number_is_offered_for_review_not_hidden(): void
    {
        $this->reads(self::CARD, [], ['N01-23-456789' => 55.0]);
        $response = $this->autofill()->assertJsonPath('status', 'partial')
            ->assertJsonPath('fields.license_number', 'N01-23-456789')->assertJsonPath('flags.license_number', 'low_confidence');
        $this->assertStringContainsString('not clearly enough for automatic approval', implode(' ', $response->json('issues')));
    }

    public function test_unreadable_photo_asks_for_a_retake_with_the_likely_cause(): void
    {
        $this->reads('ae :', ['sharpness' => 8.0, 'confidence' => 20.0]);
        $issues = implode(' ', $this->autofill()->assertJsonPath('status', 'retake')->assertJsonPath('fields', [])->json('issues'));
        $this->assertStringContainsString('out of focus', $issues);
        $this->assertStringContainsString('license number could not be read', strtolower($issues));
    }

    public function test_service_errors_invalid_files_and_other_documents_are_distinguished(): void
    {
        $this->reads(null, ['engine_available' => false, 'engine_error' => 'The OCR engine is not available on the server.']);
        $this->autofill()->assertJsonPath('status', 'unavailable');

        $this->reads(null, ['engine_available' => false, 'width' => null, 'height' => null, 'engine_error' => 'The image file could not be decoded.']);
        $this->autofill()->assertJsonPath('status', 'invalid_image')->assertJsonPath('issues.0', 'The image file could not be decoded.');

        $this->reads("OFFICIAL RECEIPT\nSARI SARI STORE CORPORATION\nTOTAL AMOUNT DUE 1,250.00 CASH 1,500.00 CHANGE 250.00");
        $this->autofill()->assertJsonPath('status', 'not_license');
    }

    public function test_expired_license_is_read_but_flagged(): void
    {
        $this->reads(str_replace('2030/05/14', '2024/01/15', self::CARD));
        $issues = implode(' ', $this->autofill()->assertJsonPath('fields.expiry_date', '2024-01-15')->json('issues'));
        $this->assertStringContainsString('has passed', $issues);
    }
}
