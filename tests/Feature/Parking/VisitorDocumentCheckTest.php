<?php
namespace Tests\Feature\Parking;

use App\Services\Parking\VisitorDocumentCheck;
use Laravel\Sanctum\Sanctum;

class VisitorDocumentCheckTest extends ParkingTestCase
{
    public function test_visitor_check_reads_license_only_and_plate_review_is_manual(): void
    {
        \Illuminate\Support\Facades\Storage::fake('private'); $disk = \Illuminate\Support\Facades\Storage::disk('private');
        $path = \Illuminate\Http\UploadedFile::fake()->image('license.png')->store('visitor-documents', 'private');
        $vehiclePath = \Illuminate\Http\UploadedFile::fake()->image('vehicle.png')->store('visitor-documents', 'private');
        $document = ['kind' => 'image', 'engine_available' => true, 'text' => 'LICENSE TEXT'];
        $reader = $this->mock(\App\Services\LicenseVerification\LicenseDocumentReader::class);
        $reader->shouldReceive('read')->once()->with($disk->path($path), 'image/png')->andReturn($document);
        $rules = $this->mock(\App\Services\LicenseVerification\LicenseValidationRules::class);
        $rules->shouldReceive('evaluate')->once()->andReturn(['checks' => ['name' => ['result' => 'pass', 'message' => 'Name matches.']], 'extracted' => []]);
        $row = (object) ['driver_name' => 'TEST DRIVER', 'driver_license_photo_path' => $path, 'vehicle_photo_path' => $vehiclePath,
            'driver_license_expiry' => '2035-01-01', 'driver_license_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('N01-23-456789')];
        $result = app(VisitorDocumentCheck::class)->check($row, 'ABC-1234');
        $this->assertSame('pass', $result['license']['checks']['name']['result']); $this->assertSame('manual', $result['plate']['result']);
        $this->assertSame('ABC-1234', $result['plate']['submitted']); $this->assertArrayNotHasKey('candidates', $result['plate']);
        $this->assertArrayNotHasKey('confidence', $result['plate']); $disk->assertExists($vehiclePath);
    }

    public function test_document_check_rejects_non_staff_before_request_lookup(): void
    {
        Sanctum::actingAs($this->user());
        $this->postJson('/api/visitors/requests/999999/document-check')->assertForbidden();
    }

    public function test_staff_cannot_check_missing_request(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/visitors/requests/999999/document-check')->assertNotFound();
    }
    public function test_background_sync_does_not_consume_document_check_limit(): void
    {
        $this->actingAsAdmin();
        for ($i = 0; $i < 8; $i++) {
            $this->getJson('/api/sync/revision')->assertOk();
        }
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/visitors/requests/999999/document-check')->assertNotFound();
        }
        $this->postJson('/api/visitors/requests/999999/document-check')->assertStatus(429);
    }
}
