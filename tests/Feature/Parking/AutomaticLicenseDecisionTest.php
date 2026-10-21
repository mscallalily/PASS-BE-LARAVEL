<?php

namespace Tests\Feature\Parking;

use App\Jobs\ProcessLicenseVerification;
use App\Models\DriverLicense;
use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\LicenseVerification\LicenseValidationRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AutomaticLicenseDecisionTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        config(['queue.default' => 'sync']);
    }

    private function fixture(string $authorization = 'approved'): array
    {
        $car = $this->campusVehicle(licenseVerification: 'processing', authorization: $authorization);
        DB::table('user_profiles')->insert([
            'user_id' => $car['user']->user_id, 'first_name' => 'Test', 'last_name' => 'Driver',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $license = DriverLicense::where('driver_id', $car['driver_id'])->firstOrFail();
        $file = UploadedFile::fake()->image('license.png', 1700, 1071);
        $license->forceFill([
            'license_image_path' => $file->store('driver-licenses', 'private'),
            'original_file_name' => 'license.png',
            'verification_source' => 'automatic', 'verification_run_id' => (string) Str::uuid(),
        ])->save();
        return [$car, $license];
    }

    private function document(DriverLicense $license, array $changes = []): array
    {
        $expiry = $license->expiry_date->format('Y/m/d');
        return $changes + [
            'kind' => 'image', 'engine_available' => true, 'engine_error' => null,
            'text' => "REPUBLIC OF THE PHILIPPINES\nLAND TRANSPORTATION OFFICE\nDEPARTMENT OF TRANSPORTATION\nNON-PROFESSIONAL DRIVER'S LICENSE\nLast Name, First Name, Middle Name\nTEST DRIVER\nNationality Sex Date of Birth\n1995/05/14\nLicense No. Expiration Date Agency Code\n{$license->license_number} {$expiry} N01\nBlood Type Restrictions Conditions",
            'confidence' => 92.0, 'width' => 1700, 'height' => 1071, 'sharpness' => 1200.0,
        ];
    }

    private function reader(array $document, int $times = 1): LicenseDocumentReader
    {
        $mock = $this->mock(LicenseDocumentReader::class);
        $mock->shouldReceive('read')->times($times)->andReturn($document);
        return $mock;
    }

    private function process(DriverLicense $license, array $changes = []): ProcessLicenseVerification
    {
        $job = new ProcessLicenseVerification($license->license_id, $license->verification_run_id);
        $job->handle($this->reader($this->document($license, $changes)), app(LicenseValidationRules::class));
        $license->refresh();
        return $job;
    }

    public function test_matching_upload_is_auto_approved_and_audited_without_exposing_private_ocr(): void
    {
        [$car, $license] = $this->fixture();
        Sanctum::actingAs($car['user']);
        $license->forceFill(['verification_status' => 'needs_correction'])->save();
        $this->reader($this->document($license));
        $this->post('/api/licenses', [
            'license_number' => $license->license_number, 'license_type' => 'non_professional',
            'expiry_date' => $license->expiry_date->toDateString(),
            'license_file' => UploadedFile::fake()->image('new-license.png', 1700, 1071),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.verification_status', 'verified')->assertJsonPath('data.verification_source', 'automatic');
        $license->refresh();
        $this->assertNotNull($license->checked_at);
        $this->assertNull($license->reviewed_by);
        $this->assertSame('unavailable', $license->ocr_result['official_verification']);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $license->license_id, 'action' => 'license_auto_verified', 'actor_label' => 'system:license-check']);
        $this->assertDatabaseHas('notifications', ['user_id' => $car['user']->user_id, 'notification_type' => 'license_verified']);
        $payload = $this->getJson('/api/licenses/mine')->assertOk()->json('data');
        $this->assertArrayNotHasKey('ocr_result', $payload);
        $this->assertArrayNotHasKey('license_image_path', $payload);
        Sanctum::actingAs($this->user());
        $this->getJson("/api/licenses/{$license->license_id}/file")->assertForbidden();
        $this->getJson('/api/licenses')->assertForbidden();
    }

    public function test_queue_dispatch_failure_leaves_submission_unapproved_and_retryable(): void
    {
        [$car, $license] = $this->fixture();
        $license->forceFill(['verification_status' => 'needs_correction'])->save();
        Sanctum::actingAs($car['user']);
        config(['queue.default' => 'missing-test-connection']);
        $this->post('/api/licenses', [
            'license_number' => $license->license_number, 'license_type' => 'non_professional',
            'expiry_date' => $license->expiry_date->toDateString(),
            'license_file' => UploadedFile::fake()->image('queue-failure.png', 1700, 1071),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.verification_status', 'pending_review');
        $this->assertSame('check_unavailable', $license->fresh()->ocr_result['decision_kind']);
    }

    public function test_document_approval_does_not_approve_account_vehicle_or_assign_rfid(): void
    {
        [$car, $license] = $this->fixture('pending');
        $car['user']->forceFill(['account_status' => 'pending'])->save();
        $assignments = DB::table('rfid_assignments')->count();
        $this->process($license);
        $this->assertSame('verified', $license->verification_status);
        $this->assertSame('pending', $car['user']->fresh()->account_status);
        $this->assertDatabaseHas('vehicle_authorizations', ['vehicle_id' => $car['vehicle_id'], 'authorization_status' => 'pending']);
        $this->assertDatabaseCount('rfid_assignments', $assignments);
        $this->actingAsStaff();
        $this->postJson('/api/parking/entry', ['rfid_tag_uid' => $car['tag']])->assertJsonPath('status', 'denied');
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
    }

    public function test_mismatched_document_is_returned_with_reasons_and_gate_denies_entry(): void
    {
        [$car, $license] = $this->fixture();
        $this->process($license, ['text' => str_replace('TEST DRIVER', 'SOMEONE ELSE', $this->document($license)['text'])]);
        $this->assertSame('needs_correction', $license->verification_status);
        $this->assertStringContainsString('name', $license->verification_reason);
        $this->actingAsStaff();
        $this->postJson('/api/parking/entry', ['rfid_tag_uid' => $car['tag']])->assertJsonPath('status', 'denied');
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
    }

    public function test_outage_stays_unapproved_and_only_current_owner_can_retry_it(): void
    {
        [$car, $license] = $this->fixture();
        $this->process($license, ['engine_available' => false, 'engine_error' => 'OCR unavailable', 'text' => '', 'confidence' => null]);
        $this->assertSame('pending_review', $license->verification_status);
        Sanctum::actingAs($this->user());
        $this->postJson("/api/licenses/{$license->license_id}/recheck")->assertForbidden();
        Sanctum::actingAs($car['user']);
        $this->reader($this->document($license));
        $this->postJson("/api/licenses/{$license->license_id}/recheck")->assertOk()->assertJsonPath('data.verification_status', 'verified');
        $this->postJson("/api/licenses/{$license->license_id}/recheck")->assertConflict();
    }

    public function test_owner_cannot_bypass_staff_hold_return_or_processing_state_with_retry(): void
    {
        [$car, $license] = $this->fixture();
        Sanctum::actingAs($car['user']);
        foreach ([['pending_review', 'staff'], ['needs_correction', 'automatic'], ['processing', 'automatic']] as [$status, $source]) {
            $license->forceFill(['verification_status' => $status, 'verification_source' => $source])->save();
            $this->postJson("/api/licenses/{$license->license_id}/recheck")->assertConflict();
        }
    }

    public function test_stale_duplicate_and_failed_jobs_cannot_overwrite_completed_or_newer_decisions(): void
    {
        [$car, $license] = $this->fixture();
        $stale = new ProcessLicenseVerification($license->license_id, (string) Str::uuid());
        $reader = $this->mock(LicenseDocumentReader::class);
        $reader->shouldNotReceive('read');
        $stale->handle($reader, app(LicenseValidationRules::class));
        $job = $this->process($license);
        $count = DB::table('notifications')->where('user_id', $car['user']->user_id)->count();
        $job->handle($reader, app(LicenseValidationRules::class));
        $job->failed(new \RuntimeException('late failure'));
        $this->assertSame('verified', $license->fresh()->verification_status);
        $this->assertSame($count, DB::table('notifications')->where('user_id', $car['user']->user_id)->count());
    }

    public function test_staff_exception_is_audited_and_new_upload_clears_previous_reviewer(): void
    {
        [$car, $license] = $this->fixture();
        $this->process($license, ['confidence' => 40.0]);
        $admin = $this->actingAsAdmin();
        $this->patchJson("/api/licenses/{$license->license_id}/review", ['decision' => 'verified', 'reason' => 'Original inspected for OCR exception.'])->assertOk();
        $license->refresh();
        $this->assertSame('staff', $license->verification_source);
        $this->assertSame($admin->user_id, $license->reviewed_by);
        $this->assertSame('Original inspected for OCR exception.', $license->verification_reason);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $license->license_id, 'action' => 'license_review_verified', 'user_id' => $admin->user_id]);
        Sanctum::actingAs($car['user']);
        $oldPath = $license->license_image_path;
        $this->reader($this->document($license));
        $this->post('/api/licenses', [
            'license_number' => $license->license_number, 'license_type' => 'non_professional',
            'expiry_date' => $license->expiry_date->toDateString(),
            'license_file' => UploadedFile::fake()->image('resubmit.png', 1700, 1071),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.verification_status', 'verified')->assertJsonPath('data.verification_source', 'automatic');
        $this->assertNull($license->fresh()->reviewed_by);
        Storage::disk('private')->assertExists($oldPath);
    }
}
