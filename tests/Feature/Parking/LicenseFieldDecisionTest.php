<?php

namespace Tests\Feature\Parking;

use App\Jobs\ProcessLicenseVerification;
use App\Models\DriverLicense;
use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\LicenseVerification\LicenseValidationRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class LicenseFieldDecisionTest extends ParkingTestCase
{
    private function read(string $text): array
    {
        $words = [];
        foreach (explode("\n", $text) as $i => $line) foreach (explode(' ', $line) as $word) {
            $words[] = ['text' => $word, 'line' => 'line-'.$i, 'confidence' => $word === 'PATTERN' ? 10.0 : 96.0];
        }
        return ['kind' => 'image', 'engine_available' => true, 'engine_error' => null,
            'text' => $text, 'words' => $words, 'confidence' => 66.0,
            'width' => 1700, 'height' => 1071, 'sharpness' => 150.0];
    }

    private function process(bool $conflict = false, bool $weak = false): array
    {
        Storage::fake('private');
        $car = $this->campusVehicle(licenseVerification: 'processing');
        $license = DriverLicense::where('driver_id', $car['driver_id'])->firstOrFail();
        $license->forceFill(['verification_run_id' => (string) Str::uuid(),
            'license_image_path' => UploadedFile::fake()->image('license.png', 1700, 1071)->store('driver-licenses', 'private')])->save();
        $header = $this->read("DEPARTMENT OF TRANSPORTATION\nLAND TRANSPORTATION OFFICE\nDRIVER'S LICENSE\n".trim(str_repeat('PATTERN ', 60)));
        $body = $this->read("Last Name First Name Middle Name\nTEST DRIVER\nNationality Sex Date of Birth\nPHL M 1995/05/14\nLicenge No, Expiration Date Agency Code\n{$license->license_number} ".$license->expiry_date->format('Y/m/d')." N01\nBlood Type Eyes Color Conditions");
        $full = $this->read($header['text']."\n".$body['text']);
        if ($conflict) {
            $wrong = str_ends_with($license->license_number, '0') ? '1' : '0';
            $full = $this->read(str_replace($license->license_number, substr($license->license_number, 0, -1).$wrong, $full['text']));
        }
        if ($weak) {
            foreach (['full', 'body'] as $variable) foreach ($$variable['words'] as &$word) {
                if ($word['text'] === $license->license_number) $word['confidence'] = 4.1;
            }
            unset($word);
            $number = $this->read($license->license_number);
            $number['words'][0]['confidence'] = 4.1;
        }
        $reader = $this->mock(LicenseDocumentReader::class);
        $reader->shouldReceive('read')->once()->andReturn($full);
        $reader->shouldReceive('readTextImage')->times($weak ? 4 : 2)->andReturn(...($weak ? [$header, $body, $number, $number] : [$header, $body]));
        (new ProcessLicenseVerification($license->license_id, $license->verification_run_id))->handle($reader, app(LicenseValidationRules::class));
        return [$car, $license->fresh()];
    }

    public function test_field_evidence_is_persisted_and_audited_but_remains_private(): void
    {
        [$car, $license] = $this->process();
        $this->assertSame('verified', $license->verification_status);
        $this->assertLessThan(80, $license->ocr_result['confidence']);
        $this->assertSame(\App\Services\LicenseVerification\LicenseValidationRules::POLICY_VERSION, $license->ocr_result['policy_version']);
        $this->assertTrue($license->ocr_result['identity_confidence']['reliable']);
        $this->assertSame('required_fields_and_corroboration', $license->ocr_result['confidence_basis']);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $license->license_id, 'action' => 'license_auto_verified']);
        Sanctum::actingAs($car['user']);
        $data = $this->getJson('/api/licenses/mine')->assertOk()->json('data');
        foreach (['ocr_result', 'identity_confidence', 'field_reads', 'text', 'license_image_path'] as $field) {
            $this->assertArrayNotHasKey($field, $data);
        }
        Sanctum::actingAs($this->user());
        $this->getJson("/api/licenses/{$license->license_id}/file")->assertForbidden();
    }

    public function test_strong_number_disagreement_stays_unapproved_and_is_audited(): void
    {
        [$car, $license] = $this->process(conflict: true);
        $this->assertSame('pending_review', $license->verification_status);
        $this->assertTrue($license->ocr_result['identity_confidence']['number_conflict']);
        $this->assertNull($license->verification_reason);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $license->license_id, 'action' => 'license_auto_pending_review']);
        $this->actingAsStaff();
        $this->postJson('/api/parking/entry', ['rfid_tag_uid' => $car['tag']])->assertJsonPath('status', 'denied');
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
    }

    public function test_exact_values_with_weak_number_remain_unapproved_and_do_not_unlock_access(): void
    {
        $tags = \Illuminate\Support\Facades\DB::table('rfid_assignments')->count();
        [$car, $license] = $this->process(weak: true);
        $this->assertSame('pending_review', $license->verification_status);
        $this->assertNull($license->verification_reason);
        $this->assertSame('ocr_review_required', $license->ocr_result['decision_kind']);
        $this->assertSame('pass', $license->ocr_result['checks']['license_number']['result']);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $license->license_id, 'action' => 'license_auto_pending_review']);
        $this->assertDatabaseCount('rfid_assignments', $tags + 1);
        Sanctum::actingAs($car['user']);
        $data = $this->getJson('/api/licenses/mine')->assertOk()->json('data');
        foreach (['ocr_result', 'identity_confidence', 'field_reads', 'confidence', 'text'] as $field) $this->assertArrayNotHasKey($field, $data);
        $this->actingAsStaff();
        $this->postJson('/api/parking/entry', ['rfid_tag_uid' => $car['tag']])->assertJsonPath('status', 'denied');
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
    }
}
