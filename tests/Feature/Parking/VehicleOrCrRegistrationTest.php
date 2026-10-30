<?php

namespace Tests\Feature\Parking;

use App\Services\LicenseVerification\LicenseDocumentReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Simplified registration: the details come from the OR/CR review, there is
 * no vehicle photo, and a repeated request never creates a second vehicle.
 */
class VehicleOrCrRegistrationTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); Storage::fake('private'); Cache::flush(); config(['queue.default' => 'sync']);
    }

    private function owner(): array
    {
        $car = $this->campusVehicle();
        $car['user']->roles()->attach(DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($car['user']);
        return $car;
    }

    /** Layout of the real LTO OR: plate and the payment column share a line. */
    private function or(string $plate, array $over = []): string
    {
        $v = $over + ['color' => 'BLACK', 'valid' => now()->addYear()->format('m/Y'), 'renewal' => now()->addYear()->format('m/d/Y')];
        return "Republic of the Philippines\nLAND TRANSPORTATION OFFICE\nOFFICIAL RECEIPT 1303-000000297665\n"
            ."Plate No: {$plate} | MVUC PHP 720.00\nClassification: Private - (PVT) | Penalty PHP 120.00\nVehicle Type: Motorcycle Without Sidecar\n"
            ."Color: {$v['color']}\nYear Model: 2024\nThis payment is valid until\n{$v['valid']} and due for renewal on\n{$v['renewal']}.\nTOTAL AMOUNT PAID: PHP 1,068.00";
    }

    private function cr(string $plate, array $over = []): string
    {
        $v = $over + ['make' => 'SUZUKI', 'series' => 'FU150MFZ', 'color' => 'COLOR: BLACK'];
        return "REPUBLIC OF THE PHILIPPINES\nLAND TRANSPORTATION OFFICE\nCERTIFICATE OF REGISTRATION\nPLATE NO. {$plate} ENGINE NO. CGA1-779901\n"
            ."MAKE: {$v['make']} SERIES: {$v['series']}\n{$v['color']}\nBODY TYPE: MOTORCYCLE WITHOUT SIDECAR\nYEAR MODEL: 2024\n"
            ."accompanied by Official Receipt as proof of payment";
    }

    private function image(string $text, array $over = []): array
    {
        return $over + ['kind' => 'image', 'engine_available' => true, 'engine_error' => null, 'text' => $text, 'confidence' => 77.0, 'width' => 2048, 'height' => 1432, 'sharpness' => 1900.0];
    }

    private function reads(array ...$documents): void
    {
        $this->mock(LicenseDocumentReader::class)->shouldReceive('read')->times(count($documents))->andReturn(...$documents);
    }

    /** What the review sends: confirmed details plus the OR and CR, no vehicle photo. */
    private function payload(string $plate, array $over = []): array
    {
        return $over + ['plate_number' => $plate, 'vehicle_type' => 'Motorcycle', 'make' => 'SUZUKI', 'model' => 'FU150MFZ', 'color' => 'BLACK', 'year_model' => 2024,
            'or_document' => UploadedFile::fake()->image('or.jpg', 2048, 1432), 'cr_document' => UploadedFile::fake()->image('cr.jpg', 2048, 1538)];
    }

    private function submit(array $data)
    {
        return $this->post('/api/vehicles', $data, ['Accept' => 'application/json']);
    }

    private function plate(): string
    {
        // Motorcycle shape (digits then letters), unique per test run.
        return random_int(100, 999).chr(random_int(65, 90)).chr(random_int(65, 90)).chr(random_int(65, 90));
    }

    public function test_confirmed_or_cr_details_register_one_vehicle_without_a_photo_and_approve_automatically(): void
    {
        $this->owner(); $plate = $this->plate(); $before = DB::table('vehicles')->count();
        // Mean confidence 77%: a sharp OR/CR printed over a security pattern is not rejected.
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate)));
        $id = $this->submit($this->payload($plate))->assertCreated()
            ->assertJsonPath('authorization_status', 'approved')->assertJsonPath('document_check.status', 'passed')->json('vehicle_id');
        $this->assertSame($before + 1, DB::table('vehicles')->count());
        $vehicle = DB::table('vehicles')->where('vehicle_id', $id)->first();
        $this->assertSame([$plate, 'SUZUKI', 'FU150MFZ', 'BLACK'], [$vehicle->plate_number, $vehicle->make, $vehicle->model, $vehicle->color]);
        $this->assertNull($vehicle->vehicle_photo_path);
        $this->assertNotNull($vehicle->or_document_path);
        $this->assertNotNull($vehicle->cr_document_path);
        $this->assertCount(2, Storage::disk('private')->allFiles());
    }

    public function test_a_repeated_request_returns_the_saved_registration_and_never_duplicates_it(): void
    {
        $this->owner(); $plate = $this->plate(); $before = DB::table('vehicles')->count();
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate)));
        $id = $this->submit($this->payload($plate))->assertCreated()->json('vehicle_id');
        // Same request again (e.g. the first response was lost): no new record, no new files, no new check.
        $this->submit($this->payload($plate))->assertOk()->assertJsonPath('vehicle_id', $id)->assertJsonPath('already_registered', true);
        $this->assertSame($before + 1, DB::table('vehicles')->count());
        $this->assertSame(1, DB::table('vehicle_authorizations')->where('vehicle_id', $id)->count());
        $this->assertCount(2, Storage::disk('private')->allFiles());
        // Different details are a new request: the plate is already registered.
        $this->submit($this->payload($plate, ['color' => 'RED']))->assertUnprocessable()->assertJsonValidationErrors('plate_number');
    }

    public function test_a_plate_registered_to_another_owner_is_rejected_without_saving_anything(): void
    {
        $other = $this->campusVehicle(); $this->owner(); $before = DB::table('vehicles')->count();
        $this->submit($this->payload($other['plate']))->assertUnprocessable()->assertJsonValidationErrors('plate_number');
        $this->assertSame($before, DB::table('vehicles')->count());
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_the_or_color_is_used_when_the_cr_color_cannot_be_read(): void
    {
        $this->owner(); $plate = $this->plate();
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate, ['color' => ''])));
        $id = $this->submit($this->payload($plate))->assertCreated()->assertJsonPath('authorization_status', 'approved')->json('vehicle_id');
        $result = json_decode(DB::table('vehicle_authorizations')->where('vehicle_id', $id)->value('document_check_result'), true);
        $this->assertSame('pass', $result['checks']['cr_color']['result']);
        $this->assertStringContainsString('OR', $result['checks']['cr_color']['detail']);
    }

    public function test_unreadable_plate_mismatches_and_wrong_documents_are_never_approved(): void
    {
        $this->owner(); $before = DB::table('vehicles')->count();
        // The plate is unreadable on both documents: retake, not approval.
        $plate = $this->plate();
        $this->reads($this->image(str_replace("Plate No: {$plate} |", 'Cost of plate', $this->or($plate))), $this->image(str_replace("PLATE NO. {$plate} ", '', $this->cr($plate))));
        $reasons = $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('saved', false)->assertJsonPath('document_check.status', 'retake_required')->json('document_check.reasons');
        $this->assertStringContainsString('either the OR or the CR', implode(' ', $reasons));

        // The confirmed plate differs from the documents.
        $plate = $this->plate(); $typed = $this->plate();
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate)));
        $this->submit($this->payload($typed))->assertUnprocessable()->assertJsonPath('saved', false)->assertJsonPath('document_check.status', 'needs_correction');

        // A CR uploaded in place of the OR.
        $plate = $this->plate();
        $this->reads($this->image($this->cr($plate)), $this->image($this->cr($plate)));
        $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('saved', false)->assertJsonPath('document_check.status', 'needs_correction');

        // An expired registration.
        $plate = $this->plate();
        $this->reads($this->image($this->or($plate, ['valid' => now()->subMonths(2)->format('m/Y'), 'renewal' => now()->subMonths(2)->format('m/d/Y')])), $this->image($this->cr($plate)));
        $reasons = $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('saved', false)->json('document_check.reasons');
        $this->assertStringContainsString('expired', implode(' ', $reasons));
        // None of these submissions was saved.
        $this->assertSame($before, DB::table('vehicles')->count());
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_a_detail_read_on_only_one_document_is_accepted_from_that_document(): void
    {
        $this->owner();
        // Plate readable on the OR only (the CR's plate area is unreadable).
        $plate = $this->plate();
        $this->reads($this->image($this->or($plate)), $this->image(str_replace("PLATE NO. {$plate} ", '', $this->cr($plate))));
        $id = $this->submit($this->payload($plate))->assertCreated()->assertJsonPath('authorization_status', 'approved')->json('vehicle_id');
        $result = $this->checkResult($id);
        $this->assertSame('uncertain', $result['checks']['cr_plate']['result']);
        $this->assertStringContainsString('OR', $result['checks']['plate_source']['detail']);

        // Plate readable on the CR only.
        $plate = $this->plate();
        $this->reads($this->image(str_replace("Plate No: {$plate} |", 'Cost of plate', $this->or($plate))), $this->image($this->cr($plate)));
        $this->submit($this->payload($plate))->assertCreated()->assertJsonPath('authorization_status', 'approved');

        // Make unreadable on the CR, series readable: the description is sufficient.
        $plate = $this->plate();
        $this->reads($this->image($this->or($plate)), $this->image(str_replace('MAKE: SUZUKI ', '', $this->cr($plate))));
        $id = $this->submit($this->payload($plate))->assertCreated()->assertJsonPath('authorization_status', 'approved')->json('vehicle_id');
        $this->assertSame('uncertain', $this->checkResult($id)['checks']['cr_make']['result']);

        // Series printed on the OR only (CR series unreadable): taken from the OR.
        $plate = $this->plate();
        $this->reads($this->image($this->or($plate)."\nMake: SUZUKI Series: FU150MFZ"), $this->image(str_replace('SERIES: FU150MFZ', '', $this->cr($plate))));
        $id = $this->submit($this->payload($plate))->assertCreated()->assertJsonPath('authorization_status', 'approved')->json('vehicle_id');
        $this->assertStringContainsString('OR', $this->checkResult($id)['checks']['cr_model']['detail']);

        // Same plate on both documents written differently ("ABC-123" / "ABC 123"): one plate.
        $plate = $this->plate();
        $dashed = substr($plate, 0, 3).'-'.substr($plate, 3);
        $this->reads($this->image($this->or($dashed)), $this->image($this->cr(substr($plate, 0, 3).' '.substr($plate, 3))));
        $this->submit($this->payload($plate))->assertCreated()->assertJsonPath('authorization_status', 'approved');
    }

    public function test_documents_that_disagree_or_lack_the_required_details_are_not_approved(): void
    {
        $this->owner(); $before = DB::table('vehicles')->count();
        // The OR and CR describe different series: correction, never a silent choice.
        $plate = $this->plate();
        $this->reads($this->image($this->or($plate)."\nMake: SUZUKI Series: RAIDER150"), $this->image($this->cr($plate)));
        $reasons = $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('saved', false)
            ->assertJsonPath('document_check.status', 'needs_correction')->json('document_check.reasons');
        $this->assertStringContainsString('model (series) on the OR ("RAIDER150") does not match the CR', implode(' ', $reasons));

        // Series unreadable on both documents: retake (the confirmed value is not proof).
        $plate = $this->plate();
        $this->reads($this->image($this->or($plate)), $this->image(str_replace('SERIES: FU150MFZ', '', $this->cr($plate))));
        $reasons = $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('saved', false)->assertJsonPath('document_check.status', 'retake_required')->json('document_check.reasons');
        $this->assertStringContainsString('model (series) could not be read on the OR or the CR', implode(' ', $reasons));

        // Plate and series missing from both: nothing is approved.
        $plate = $this->plate();
        $this->reads($this->image(str_replace("Plate No: {$plate} |", 'Cost of plate', $this->or($plate))),
            $this->image(str_replace(["PLATE NO. {$plate} ", 'SERIES: FU150MFZ'], '', $this->cr($plate))));
        $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('saved', false)->assertJsonPath('document_check.status', 'retake_required');
        $this->assertSame($before, DB::table('vehicles')->count());
    }

    private function checkResult(int $vehicleId): array
    {
        return json_decode(DB::table('vehicle_authorizations')->where('vehicle_id', $vehicleId)->orderByDesc('authorization_id')->value('document_check_result'), true);
    }

    private function autofillCr(string $text, array $over = [])
    {
        $this->mock(LicenseDocumentReader::class)->shouldReceive('read')->once()->andReturn($this->image($text, $over));
        return $this->post('/api/documents/autofill', ['document_type' => 'vehicle_cr', 'document' => UploadedFile::fake()->image('cr.jpg', 2048, 1538)], ['Accept' => 'application/json'])->assertOk();
    }

    public function test_cr_review_names_only_the_field_that_needs_attention(): void
    {
        $this->owner(); $plate = $this->plate();
        // Plate readable, series missing: only the series is flagged, with a note for the
        // review (which may still get the series from the OR); no document-level issue.
        $r = $this->autofillCr(str_replace('SERIES: FU150MFZ', '', $this->cr($plate)))
            ->assertJsonPath('status', 'partial')->assertJsonPath('fields.plate_number', substr($plate, 0, 3).' '.substr($plate, 3))
            ->assertJsonPath('flags.model', 'missing')->assertJsonMissingPath('flags.plate_number')->assertJsonMissingPath('notes.plate_number');
        $this->assertStringContainsString('SERIES', $r->json('notes.model'));
        $this->assertSame([], $r->json('issues'));
        // A series read with look-alike characters is offered, but marked for checking.
        $this->autofillCr($this->cr($plate, ['series' => 'FU1SOMFZ']))
            ->assertJsonPath('fields.model', 'FU1SOMFZ')->assertJsonPath('flags.model', 'low_confidence');
    }

    public function test_a_plate_already_registered_to_someone_else_is_flagged_at_review(): void
    {
        $other = $this->campusVehicle(); $this->owner();
        $r = $this->autofillCr($this->cr($other['plate']))->assertJsonPath('flags.plate_number', 'conflict');
        $this->assertStringContainsString('already registered', implode(' ', $r->json('issues')));
    }

    public function test_a_series_read_with_look_alike_characters_still_matches_the_confirmed_series(): void
    {
        $this->owner(); $plate = $this->plate();
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate, ['series' => 'FU1SOMFZ'])));
        $this->submit($this->payload($plate))->assertCreated()->assertJsonPath('authorization_status', 'approved');
        // A genuinely different series is still a correction.
        $plate = $this->plate();
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate, ['series' => 'FU125MFZ'])));
        $before = DB::table('vehicles')->count();
        $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('saved', false)->assertJsonPath('document_check.status', 'needs_correction');
        $this->assertSame($before, DB::table('vehicles')->count());
    }

    public function test_the_existing_license_requirement_still_applies(): void
    {
        $car = $this->owner(); $before = DB::table('vehicles')->count();
        DB::table('driver_licenses')->where('driver_id', $car['driver_id'])->update(['verification_status' => 'needs_correction']);
        $this->submit($this->payload($this->plate()))->assertUnprocessable()->assertJsonValidationErrors('license');
        $this->assertSame($before, DB::table('vehicles')->count());
        $this->assertSame([], Storage::disk('private')->allFiles());
    }
}
