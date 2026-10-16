<?php
namespace Tests\Feature\Parking;

use App\Services\Parking\VisitorDocumentCheck;
use Laravel\Sanctum\Sanctum;

class VisitorDocumentCheckTest extends ParkingTestCase
{
    public function test_plate_comparison_uses_complete_candidates_and_keeps_ocr_ambiguity(): void
    {
        $this->assertSame('pass', VisitorDocumentCheck::comparePlate('Plate NGM 8227', 'NGM-8227', 85)['result']);
        $this->assertSame('fail', VisitorDocumentCheck::comparePlate('NGM 8228', 'NGM-8227', 85)['result']);
        $this->assertSame('uncertain', VisitorDocumentCheck::comparePlate('NGM 8228', 'NGM-8227', 45)['result']);
        $this->assertSame('uncertain', VisitorDocumentCheck::comparePlate('NGM 82270', 'NGM-8227', 90)['result']);
        $this->assertSame('uncertain', VisitorDocumentCheck::comparePlate('Unreadable', 'NGM-8227', null)['result']);
        $this->assertSame('uncertain', VisitorDocumentCheck::comparePlate('ABC 1234 XYZ 1234', 'NGM-8227', 90)['result']);
        $this->assertSame('pass', VisitorDocumentCheck::comparePlate('123 ABC', '123ABC', 90)['result']);
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
}
