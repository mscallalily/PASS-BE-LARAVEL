<?php

namespace Tests\Feature\Parking;

use App\Services\Parking\VisitorPassPolicy;
use App\Support\CampusTime;
use Illuminate\Support\Facades\DB;

class VisitorQrTest extends ParkingTestCase
{
    private function requestPayload(array $overrides = []): array
    {
        $now = CampusTime::now();

        return $overrides + [
            'first_name' => 'Rhea',
            'last_name' => 'Santos',
            'contact_number' => '09171234567',
            'plate_number' => 'NEW ' . random_int(1000, 9999),
            'vehicle_type_id' => DB::table('vehicle_types')->where('type_code', 'sedan')->value('vehicle_type_id'),
            'purpose' => 'Enrollment inquiry',
            'visiting' => 'Registrar',
            'valid_from' => $now->subMinutes(30)->format('Y-m-d\TH:i'),
            'valid_until' => $now->addHours(4)->format('Y-m-d\TH:i'),
        ];
    }

    private function approvedPass(array $overrides = []): array
    {
        $id = $this->postJson('/api/visitors/requests', $this->requestPayload($overrides))->assertCreated()->json('request_id');

        return $this->postJson("/api/visitors/requests/{$id}/approve")->assertOk()->json('pass') + ['request_id' => $id];
    }

    public function test_approving_a_request_issues_one_pass_linked_to_request_visitor_and_vehicle(): void
    {
        $this->actingAsStaff();
        $payload = $this->requestPayload();

        $id = $this->postJson('/api/visitors/requests', $payload)->assertCreated()->json('request_id');
        $request = DB::table('visitor_requests')->where('request_id', $id)->first();
        $this->assertSame('pending', $request->request_status);
        $this->assertFalse(DB::table('qr_passes')->where('request_id', $id)->exists(), 'No pass before approval.');

        $pass = $this->postJson("/api/visitors/requests/{$id}/approve")->assertOk()->json('pass');

        $row = DB::table('qr_passes')->where('qr_pass_id', $pass['qr_pass_id'])->first();
        $this->assertSame($id, (int) $row->request_id);
        $this->assertSame((int) $request->visitor_id, (int) $row->visitor_id);
        $this->assertSame((int) $request->vehicle_id, (int) $row->vehicle_id);
        $this->assertSame($request->requested_valid_until, $row->valid_until);
        $this->assertSame(hash('sha256', $row->pass_code), $row->token_hash);
        $this->assertSame('approved', DB::table('visitor_requests')->where('request_id', $id)->value('request_status'));
        $this->assertSame(strtoupper($payload['plate_number']), DB::table('vehicles')->where('vehicle_id', $row->vehicle_id)->value('plate_number'));

        // Approving twice does not issue a second pass.
        $this->postJson("/api/visitors/requests/{$id}/approve")->assertStatus(409);
        $this->assertSame(1, DB::table('qr_passes')->where('request_id', $id)->count());
    }

    public function test_visitor_enters_and_exits_with_an_approved_pass_which_is_then_used_up(): void
    {
        $this->actingAsStaff();
        $pass = $this->approvedPass();
        $vehicleId = DB::table('qr_passes')->where('qr_pass_id', $pass['qr_pass_id'])->value('vehicle_id');

        $in = $this->gate('entry', ['qr_pass_code' => $pass['pass_code']]);

        $this->assertSame('granted', $in['status']);
        $txn = $this->activeTransaction($vehicleId);
        $this->assertSame('qr', $txn->access_method);
        $this->assertNotNull($txn->visitor_id);
        $this->assertNull($txn->user_id);

        $this->assertSame('completed', $this->gate('exit', ['qr_pass_code' => $pass['pass_code']])['status']);
        $this->assertSame('used', DB::table('qr_passes')->where('qr_pass_id', $pass['qr_pass_id'])->value('pass_status'));

        $again = $this->gate('entry', ['qr_pass_code' => $pass['pass_code']]);
        $this->assertSame('denied', $again['status']);
        $this->assertSame('Visitor QR pass has already been used.', $again['reason']);
    }

    public function test_rejected_request_issues_no_pass_and_cannot_be_approved_later(): void
    {
        $this->actingAsStaff();
        $id = $this->postJson('/api/visitors/requests', $this->requestPayload())->assertCreated()->json('request_id');

        $this->postJson("/api/visitors/requests/{$id}/reject", [])->assertUnprocessable();
        $this->postJson("/api/visitors/requests/{$id}/reject", ['remarks' => 'No appointment'])->assertOk();

        $this->assertFalse(DB::table('qr_passes')->where('request_id', $id)->exists());
        $this->postJson("/api/visitors/requests/{$id}/approve")->assertStatus(409);
    }

    public function test_revoked_pass_is_denied(): void
    {
        $this->actingAsStaff();
        $pass = $this->approvedPass();

        $this->postJson("/api/visitors/passes/{$pass['qr_pass_id']}/revoke", ['reason' => 'Visit cancelled'])->assertOk();
        $r = $this->gate('entry', ['qr_pass_code' => $pass['pass_code']]);

        $this->assertSame('denied', $r['status']);
        $this->assertSame('Visitor QR pass was revoked.', $r['reason']);
    }

    public function test_expired_pass_is_denied(): void
    {
        $this->actingAsStaff();
        $pass = $this->approvedPass();

        $this->travel(5)->hours();
        $r = $this->gate('entry', ['qr_pass_code' => $pass['pass_code']]);

        $this->assertSame('denied', $r['status']);
        $this->assertSame('Visitor QR pass has expired.', $r['reason']);
        $this->assertSame('expired', $this->getJson('/api/visitors/passes/lookup?code=' . $pass['pass_code'])->json('status'));
    }

    public function test_legacy_pass_without_an_approved_request_cannot_enter_but_can_exit(): void
    {
        $this->actingAsStaff();
        $visitor = $this->visitorVehicle();
        $legacy = $this->rawPass(['visitor_id' => $visitor['visitor_id'], 'vehicle_id' => $visitor['vehicle_id']]);

        $r = $this->gate('entry', ['qr_pass_code' => $legacy]);
        $this->assertSame('denied', $r['status']);
        $this->assertSame(VisitorPassPolicy::LEGACY_REASON, $r['reason']);
        $this->assertSame('denied', $this->gate('entry', ['plate_number' => $visitor['plate']])['status'], 'Plate override does not bypass approval.');
        $this->assertSame('legacy', $this->getJson('/api/visitors/passes/lookup?code=' . $legacy)->json('status'));

        // A visitor already inside with a legacy pass can still leave.
        DB::table('parking_transactions')->insert([
            'ticket_number' => 'PT-TEST-LEGACY', 'vehicle_id' => $visitor['vehicle_id'], 'visitor_id' => $visitor['visitor_id'],
            'parking_area_id' => DB::table('parking_areas')->where('area_code', 'CAR')->value('parking_area_id'),
            'access_method' => 'qr', 'entry_datetime' => now()->subHour(), 'transaction_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertSame('completed', $this->gate('exit', ['qr_pass_code' => $legacy])['status']);
    }

    public function test_plate_of_a_campus_user_cannot_be_registered_and_nothing_is_saved(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $visitorsBefore = DB::table('visitors')->count();

        $this->postJson('/api/visitors/requests', $this->requestPayload(['plate_number' => $car['plate']]))
            ->assertUnprocessable()->assertJsonValidationErrors('plate_number');

        $this->assertSame($visitorsBefore, DB::table('visitors')->count(), 'The new visitor row is rolled back.');
    }

    public function test_overlapping_request_for_the_same_vehicle_is_refused(): void
    {
        $this->actingAsStaff();
        $payload = $this->requestPayload();
        $this->postJson('/api/visitors/requests', $payload)->assertCreated();
        $visitorId = DB::table('visitors')->orderByDesc('visitor_id')->value('visitor_id');

        $this->postJson('/api/visitors/requests', ['visitor_id' => $visitorId] + $payload)->assertStatus(409);
    }

    public function test_regular_users_cannot_manage_visitors(): void
    {
        $this->actingAs($this->user('student'), 'sanctum');

        $this->getJson('/api/visitors/requests')->assertForbidden();
        $this->postJson('/api/visitors/requests', $this->requestPayload())->assertForbidden();
    }
}
