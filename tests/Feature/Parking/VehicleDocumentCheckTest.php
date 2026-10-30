<?php

namespace Tests\Feature\Parking;

use App\Jobs\ProcessVehicleDocumentCheck;
use App\Services\LicenseVerification\LicenseDocumentReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Automatic OR/CR approval for campus vehicles: a registration is saved and
 * approved only when every document and eligibility check passes; otherwise
 * nothing is saved and the owner gets the reasons. There is no manual staff
 * approval queue.
 */
class VehicleDocumentCheckTest extends ParkingTestCase
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

    private function cr(string $plate, array $over = []): string
    {
        $v = $over + ['make' => 'TOYOTA', 'series' => 'VIOS 1.3 E', 'year' => '2019', 'color' => 'PEARL WHITE', 'body' => 'SEDAN'];
        return "REPUBLIC OF THE PHILIPPINES\nLAND TRANSPORTATION OFFICE\nCERTIFICATE OF REGISTRATION\nMV FILE NO. 1301-0000012345 PLATE NO. {$plate}\n"
            ."MAKE: {$v['make']} SERIES: {$v['series']}\nYEAR MODEL: {$v['year']} COLOR: {$v['color']}\nBODY TYPE: {$v['body']}\nENGINE NO. 2NR1234567 CHASSIS NO. MHF000000";
    }

    private function or(string $plate, ?string $expiry = null): string
    {
        $expiry ??= now()->addYear()->format('m/d/Y');
        return "REPUBLIC OF THE PHILIPPINES\nLAND TRANSPORTATION OFFICE\nOFFICIAL RECEIPT\nPLATE NO. {$plate}\nMAKE: TOYOTA\nREGISTRATION EXPIRY DATE: {$expiry}\nAMOUNT PAID 1,600.00";
    }

    private function image(string $text, array $over = []): array
    {
        return $over + ['kind' => 'image', 'engine_available' => true, 'engine_error' => null, 'text' => $text, 'confidence' => 91.0, 'width' => 1600, 'height' => 1100, 'sharpness' => 900.0];
    }

    /** The job reads the OR first, then the CR. */
    private function reads(array ...$documents): void
    {
        $this->mock(LicenseDocumentReader::class)->shouldReceive('read')->times(count($documents))->andReturn(...$documents);
    }

    private function payload(string $plate, array $over = []): array
    {
        return $over + ['plate_number' => $plate, 'vehicle_type' => 'Sedan', 'make' => 'TOYOTA', 'model' => 'VIOS', 'color' => 'WHITE', 'year_model' => 2019,
            'vehicle_photo' => UploadedFile::fake()->image('vehicle.png', 1200, 800),
            'or_document' => UploadedFile::fake()->image('or.jpg', 1600, 1100), 'cr_document' => UploadedFile::fake()->image('cr.jpg', 1600, 1100)];
    }

    private function submit(array $data)
    {
        return $this->post('/api/vehicles', $data, ['Accept' => 'application/json']);
    }

    private function latest(int $vehicleId): object
    {
        return DB::table('vehicle_authorizations')->where('vehicle_id', $vehicleId)->orderByDesc('authorization_id')->first();
    }

    /** Nothing from a refused submission is kept: no vehicle, authorization or file. */
    private function assertNothingSaved(int $vehicles, int $authorizations, string $message = ''): void
    {
        $this->assertSame($vehicles, DB::table('vehicles')->count(), $message);
        $this->assertSame($authorizations, DB::table('vehicle_authorizations')->count(), $message);
        $this->assertSame([], Storage::disk('private')->allFiles(), $message);
    }

    /**
     * A registration saved before the check ran ahead of saving (still pending
     * with its OR/CR on file): the retry and correction flows still serve it.
     */
    private function legacyPending(string $status): array
    {
        $car = $this->owner();
        $paths = [];
        foreach (['or', 'cr'] as $kind) $paths[$kind] = UploadedFile::fake()->image("{$kind}.jpg", 1600, 1100)->store('vehicle-documents', 'private');
        DB::table('vehicles')->where('vehicle_id', $car['vehicle_id'])->update(['make' => 'TOYOTA', 'model' => 'VIOS', 'color' => 'WHITE', 'year_model' => 2019,
            'or_document_path' => $paths['or'], 'cr_document_path' => $paths['cr']]);
        DB::table('rfid_assignments')->where('vehicle_id', $car['vehicle_id'])->update(['assignment_status' => 'revoked', 'unassigned_at' => now()]);
        DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->update(['authorization_status' => 'pending',
            'document_check_status' => $status, 'document_check_run_id' => (string) \Illuminate\Support\Str::uuid()]);
        return $car;
    }

    public function test_matching_or_cr_is_approved_automatically_without_staff_and_rfid_stays_separate(): void
    {
        $car = $this->owner(); $plate = $this->nextTestPlate('ORC'); $tags = DB::table('rfid_assignments')->count();
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate)));
        $r = $this->submit($this->payload($plate) + ['authorization_status' => 'approved', 'reviewed_by' => 1])->assertCreated()
            ->assertJsonPath('authorization_status', 'approved')->assertJsonPath('document_check.status', 'passed')
            ->assertJsonPath('ownership_verified', false)->assertJsonPath('authenticity_verified', false);
        $auth = $this->latest($r->json('vehicle_id'));
        $this->assertSame('automatic', $auth->approval_source);
        $this->assertNull($auth->reviewed_by);
        $this->assertStringContainsString('not verified', $auth->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'authorization_auto_approved', 'auditable_id' => $auth->authorization_id, 'actor_label' => 'system:vehicle-document-check']);
        $this->assertDatabaseHas('notifications', ['user_id' => $car['user']->user_id, 'notification_type' => 'vehicle_auto_approved']);
        $this->assertDatabaseCount('rfid_assignments', $tags);
        // Owner data never exposes storage paths.
        $row = collect($this->getJson('/api/vehicles')->assertOk()->json('vehicles'))->firstWhere('id', (string) $r->json('vehicle_id'));
        foreach (['or_document_path', 'cr_document_path', 'vehicle_photo_path'] as $key) $this->assertArrayNotHasKey($key, $row);
        $this->assertSame('passed', $row['documentCheck']['status']);
    }

    public function test_unreadable_document_requires_a_retake_and_nothing_is_saved(): void
    {
        $this->owner(); $plate = $this->nextTestPlate('BLR');
        $vehicles = DB::table('vehicles')->count(); $authorizations = DB::table('vehicle_authorizations')->count();
        $this->reads($this->image($this->or($plate), ['sharpness' => 12.0]), $this->image($this->cr($plate), ['confidence' => 41.0]));
        $response = $this->submit($this->payload($plate))->assertUnprocessable()
            ->assertJsonPath('saved', false)->assertJsonPath('document_check.status', 'retake_required')->assertJsonValidationErrors('documents');
        $reasons = implode(' ', $response->json('document_check.reasons'));
        // The CR's text is mostly unreadable (41%). A low sharpness score alone does not
        // reject the OR, whose text was read with high confidence.
        $this->assertStringContainsString('Retake the Certificate of Registration', $reasons);
        $this->assertStringNotContainsString('Retake the Official Receipt', $reasons);
        $this->assertStringStartsWith('Not registered', $response->json('message'));
        $this->assertNothingSaved($vehicles, $authorizations);
    }

    public function test_conflicting_plates_wrong_details_expired_or_and_wrong_category_are_refused_without_saving(): void
    {
        $this->owner();
        $cases = [
            'plates differ' => fn ($p) => [$this->or('ZZZ 9999'), $this->cr($p), 'do not match'],
            'color differs' => fn ($p) => [$this->or($p), $this->cr($p, ['color' => 'RED']), 'The color on the CR is "RED"'],
            'or expired' => fn ($p) => [$this->or($p, now()->subDay()->format('m/d/Y')), $this->cr($p), 'registration expired'],
            'motorcycle cr' => fn ($p) => [$this->or($p), $this->cr($p, ['body' => 'MOTORCYCLE W/O SIDECAR']), 'The CR describes a motorcycle'],
        ];
        foreach ($cases as $name => $case) {
            $plate = $this->nextTestPlate('COR');
            [$or, $cr, $expected] = $case($plate);
            $vehicles = DB::table('vehicles')->count(); $authorizations = DB::table('vehicle_authorizations')->count();
            $this->reads($this->image($or), $this->image($cr));
            Cache::flush();
            $response = $this->submit($this->payload($plate))->assertUnprocessable();
            $this->assertSame('needs_correction', $response->json('document_check.status'), $name);
            $this->assertStringContainsString($expected, implode(' ', $response->json('document_check.reasons')), $name);
            $this->assertNothingSaved($vehicles, $authorizations, $name);
            $this->assertFalse(DB::table('vehicles')->where('plate_number', $plate)->exists(), $name);
        }
    }

    public function test_missing_required_value_asks_for_a_clearer_photo_instead_of_guessing(): void
    {
        $this->owner(); $plate = $this->nextTestPlate('MIS');
        $vehicles = DB::table('vehicles')->count(); $authorizations = DB::table('vehicle_authorizations')->count();
        $crWithoutModel = str_replace(' SERIES: VIOS 1.3 E', '', $this->cr($plate));
        $this->reads($this->image($this->or($plate)), $this->image($crWithoutModel));
        $response = $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('document_check.status', 'retake_required');
        $this->assertStringContainsString('model (series) could not be read', implode(' ', $response->json('document_check.reasons')));
        $this->assertNothingSaved($vehicles, $authorizations);
    }

    public function test_passing_documents_do_not_bypass_license_requirements(): void
    {
        $car = $this->owner(); $plate = $this->nextTestPlate('LIC');
        $vehicles = DB::table('vehicles')->count(); $authorizations = DB::table('vehicle_authorizations')->count();
        // The license changes to unverified after submission validation, while the documents are read.
        $this->mock(LicenseDocumentReader::class)->shouldReceive('read')->twice()->andReturnUsing(function () use ($car, $plate) {
            static $call = 0;
            DB::table('driver_licenses')->where('driver_id', $car['driver_id'])->update(['verification_status' => 'needs_correction']);
            return $this->image($call++ === 0 ? $this->or($plate) : $this->cr($plate));
        });
        $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonValidationErrors('license');
        $this->assertNothingSaved($vehicles, $authorizations);
    }

    public function test_an_outage_saves_nothing_and_a_saved_pending_check_can_be_retried_by_its_owner_only(): void
    {
        $this->owner(); $plate = $this->nextTestPlate('OUT');
        $vehicles = DB::table('vehicles')->count(); $authorizations = DB::table('vehicle_authorizations')->count();
        $down = ['engine_available' => false, 'engine_error' => 'OCR unavailable', 'text' => '', 'confidence' => null];
        $this->reads($this->image('', $down), $this->image('', $down));
        $this->submit($this->payload($plate))->assertUnprocessable()->assertJsonPath('document_check.status', 'unavailable');
        $this->assertNothingSaved($vehicles, $authorizations);
        // Once the reader is back, the same submission registers and is approved.
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate)));
        $this->submit($this->payload($plate))->assertCreated()->assertJsonPath('authorization_status', 'approved');

        // A registration saved earlier whose check could not finish can still be retried, by its owner only.
        $car = $this->legacyPending('unavailable');
        $id = $car['vehicle_id'];
        Sanctum::actingAs($this->user());
        $this->postJson("/api/vehicles/{$id}/document-check/retry")->assertForbidden();
        Sanctum::actingAs($car['user']);
        $this->reads($this->image($this->or($car['plate'])), $this->image($this->cr($car['plate'])));
        $this->postJson("/api/vehicles/{$id}/document-check/retry")->assertOk()->assertJsonPath('authorization_status', 'approved');
        $this->postJson("/api/vehicles/{$id}/document-check/retry")->assertConflict();
    }

    public function test_a_correction_is_saved_only_when_it_passes_and_a_stale_job_cannot_overwrite_it(): void
    {
        $car = $this->legacyPending('needs_correction'); $plate = $car['plate']; $id = $car['vehicle_id'];
        $first = $this->latest($id); $authorizations = DB::table('vehicle_authorizations')->count();
        $saved = DB::table('vehicles')->where('vehicle_id', $id)->first();
        // A correction the documents still contradict changes nothing.
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate, ['color' => 'RED'])));
        $this->post("/api/vehicles/{$id}/resubmit", $this->payload($plate), ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonPath('document_check.status', 'needs_correction');
        $this->assertSame($authorizations, DB::table('vehicle_authorizations')->count());
        $this->assertEquals($saved, DB::table('vehicles')->where('vehicle_id', $id)->first());
        $this->assertCount(2, Storage::disk('private')->allFiles());
        // The corrected color matches: a new authorization is saved and approved.
        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate, ['color' => 'RED'])));
        $this->post("/api/vehicles/{$id}/resubmit", $this->payload($plate, ['color' => 'RED']), ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('authorization_status', 'approved');
        $this->assertDatabaseHas('vehicle_authorizations', ['authorization_id' => $first->authorization_id, 'authorization_status' => 'pending', 'document_check_status' => 'needs_correction']);
        // A delayed job for the old run changes nothing.
        $reader = $this->mock(LicenseDocumentReader::class); $reader->shouldNotReceive('read');
        (new ProcessVehicleDocumentCheck($first->authorization_id, (string) $first->document_check_run_id))
            ->handle(app(\App\Services\Vehicles\VehicleDocumentCheck::class), app(\App\Services\Vehicles\VehicleApprovalEligibility::class));
        $this->assertSame('approved', $this->latest($id)->authorization_status);
    }

    public function test_documents_are_required_images_and_private(): void
    {
        $car = $this->owner(); $plate = $this->nextTestPlate('DOC'); $before = DB::table('vehicles')->count();
        $payload = $this->payload($plate);
        unset($payload['cr_document']);
        $this->submit($payload)->assertUnprocessable()->assertJsonValidationErrors('cr_document');
        $this->submit($this->payload($plate, ['or_document' => UploadedFile::fake()->create('or.pdf', 200, 'application/pdf')]))->assertUnprocessable()->assertJsonValidationErrors('or_document');
        $this->assertDatabaseCount('vehicles', $before);
        $this->assertSame([], Storage::disk('private')->allFiles());

        $this->reads($this->image($this->or($plate)), $this->image($this->cr($plate)));
        $id = $this->submit($this->payload($plate))->assertCreated()->json('vehicle_id');
        $this->get("/api/vehicles/{$id}/documents/or")->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get("/api/vehicles/{$id}/documents/xx")->assertNotFound();
        Sanctum::actingAs($this->user());
        $this->get("/api/vehicles/{$id}/documents/cr")->assertForbidden();
        $this->actingAsStaff();
        $this->get("/api/vehicles/{$id}/documents/cr")->assertOk();
    }

    public function test_latest_pending_cannot_receive_rfid_or_enter_using_an_old_approved_authorization(): void
    {
        $car = $this->campusVehicle();
        DB::table('vehicle_authorizations')->insert(['vehicle_id' => $car['vehicle_id'], 'authorization_status' => 'pending', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAsStaff();
        $this->postJson('/api/parking/entry', ['rfid_tag_uid' => $car['tag']])->assertJsonPath('status', 'denied');
        DB::table('rfid_assignments')->where('vehicle_id', $car['vehicle_id'])->update(['assignment_status' => 'revoked', 'unassigned_at' => now()]);
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/assignments', ['vehicle_id' => $car['vehicle_id'], 'tag_uid' => 'PENDING-MUST-NOT-ISSUE'])->assertUnprocessable();
        $this->assertDatabaseMissing('rfid_tags', ['tag_uid' => 'PENDING-MUST-NOT-ISSUE']);
        $this->assertDatabaseCount('parking_transactions', 0);
    }

    public function test_restored_vehicle_is_not_approved_without_a_new_check(): void
    {
        $car = $this->campusVehicle(); $this->actingAsAdmin();
        $base = '/api/admin/record-archives/vehicles/'.$car['vehicle_id'];
        $this->postJson($base.'/archive', ['reason' => 'Vehicle was retired.'])->assertOk();
        $this->postJson($base.'/restore', ['reason' => 'Vehicle is in service again.'])->assertOk();
        $auth = $this->latest($car['vehicle_id']);
        $this->assertSame('pending', $auth->authorization_status);
        $this->assertSame('needs_correction', $auth->document_check_status);
    }

    public function test_autofill_returns_only_form_fields_with_flags_and_no_raw_text(): void
    {
        $car = $this->owner(); $plate = $this->nextTestPlate('AUT');
        $this->reads($this->image(str_replace(' SERIES: VIOS 1.3 E', '', $this->cr($plate))));
        $r = $this->post('/api/documents/autofill', ['document_type' => 'vehicle_cr', 'document' => UploadedFile::fake()->image('cr.jpg', 1600, 1100)], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('fields.plate_number', $plate)->assertJsonPath('fields.make', 'TOYOTA')
            ->assertJsonPath('fields.vehicle_type', 'Sedan')->assertJsonPath('flags.model', 'missing')->assertJsonMissingPath('text');
        $this->assertArrayNotHasKey('owner', $r->json('fields'));
        $this->assertSame([], Storage::disk('private')->allFiles());
        $this->reads($this->image($this->cr($plate), ['confidence' => 40.0]));
        $this->post('/api/documents/autofill', ['document_type' => 'vehicle_cr', 'document' => UploadedFile::fake()->image('cr.jpg', 1600, 1100)], ['Accept' => 'application/json'])
            // Mostly unreadable text asks for a retake of the whole photo; no field values are guessed from it.
            ->assertOk()->assertJsonPath('status', 'retake')->assertJsonPath('fields', []);
        $this->post('/api/documents/autofill', ['document_type' => 'vehicle_cr', 'document' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable();
        Sanctum::actingAs($this->user('security'));
        $this->post('/api/documents/autofill', ['document_type' => 'driver_license', 'document' => UploadedFile::fake()->image('l.jpg')], ['Accept' => 'application/json'])->assertForbidden();
    }
}
