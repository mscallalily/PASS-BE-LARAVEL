<?php

namespace Tests\Feature\Parking;

use App\Models\DriverLicense;
use App\Services\LicenseVerification\LicenseDocumentReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class LicenseApprovalLockTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); Storage::fake('private'); config(['queue.default' => 'sync']);
    }

    private function fixture(string $expiry, string $source = 'automatic'): array
    {
        $car = $this->campusVehicle();
        DB::table('user_profiles')->insert(['user_id' => $car['user']->user_id, 'first_name' => 'Test', 'last_name' => 'Driver', 'created_at' => now(), 'updated_at' => now()]);
        $license = DriverLicense::where('driver_id', $car['driver_id'])->firstOrFail();
        $license->forceFill(['expiry_date' => $expiry, 'verification_source' => $source,
            'license_image_path' => UploadedFile::fake()->image('approved.png', 1700, 1071)->store('driver-licenses', 'private')])->save();
        Sanctum::actingAs($car['user']);
        return [$car, $license->fresh()];
    }

    public function test_approved_current_license_cannot_be_replaced_through_the_api(): void
    {
        $reader = $this->mock(LicenseDocumentReader::class); $reader->shouldNotReceive('read');
        foreach (['automatic', 'staff'] as $source) {
            [$car, $license] = $this->fixture(now()->addYears(2)->toDateString(), $source);
            $before = $license->getAttributes(); $files = Storage::disk('private')->allFiles();
            $this->getJson('/api/licenses/mine')->assertOk()->assertJsonPath('data.can_resubmit', false);
            $this->post('/api/licenses', ['license_number' => $license->license_number,
                'expiry_date' => now()->addYears(3)->toDateString(), 'license_file' => UploadedFile::fake()->image('replacement.png', 1700, 1071)], ['Accept' => 'application/json'])
                ->assertConflict()->assertJsonPath('code', 'license_already_verified');
            $this->assertSame($before, $license->fresh()->getAttributes());
            $this->assertSame($files, Storage::disk('private')->allFiles());
            $this->assertDatabaseMissing('audit_logs', ['auditable_id' => $license->license_id, 'action' => 'license_resubmitted']);
        }
    }

    public function test_approval_stays_locked_through_the_printed_expiry_day(): void
    {
        [$car, $license] = $this->fixture(now('Asia/Manila')->toDateString());
        $this->getJson('/api/licenses/mine')->assertOk()->assertJsonPath('data.can_resubmit', false);
        $this->post('/api/licenses', ['license_number' => $license->license_number,
            'expiry_date' => now()->addYears(2)->toDateString(), 'license_file' => UploadedFile::fake()->image('replacement.png', 1700, 1071)], ['Accept' => 'application/json'])->assertConflict();
    }

    public function test_expired_approved_license_can_be_renewed_and_its_old_document_is_retained(): void
    {
        [$car, $license] = $this->fixture(now('Asia/Manila')->subDay()->toDateString());
        $this->getJson('/api/licenses/mine')->assertOk()->assertJsonPath('data.can_resubmit', true);
        $oldPath = $license->license_image_path; $newExpiry = now()->addYears(3)->toDateString(); $ocrDate = str_replace('-', '/', $newExpiry);
        $reader = $this->mock(LicenseDocumentReader::class); $reader->shouldReceive('read')->once()->andReturn([
            'kind' => 'image', 'engine_available' => true, 'engine_error' => null, 'confidence' => 96.0, 'width' => 1700, 'height' => 1071, 'sharpness' => 150.0,
            'text' => "REPUBLIC OF THE PHILIPPINES\nDEPARTMENT OF TRANSPORTATION\nLAND TRANSPORTATION OFFICE\nDRIVER'S LICENSE\nLast Name First Name Middle Name\nTEST DRIVER\nNationality Sex Date of Birth\n1995/05/14\nLicense No. Expiration Date Agency Code\n{$license->license_number} {$ocrDate} N01\nBlood Type Eyes Color",
        ]);
        $this->post('/api/licenses', ['license_number' => $license->license_number, 'expiry_date' => $newExpiry,
            'license_file' => UploadedFile::fake()->image('renewed.png', 1700, 1071)], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.verification_status', 'verified')->assertJsonPath('data.can_resubmit', false);
        Storage::disk('private')->assertExists($oldPath);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $license->license_id, 'action' => 'license_resubmitted']);
    }
}
