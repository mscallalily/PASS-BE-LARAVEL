<?php

namespace Tests\Feature\Parking;

use App\Models\User;
use App\Support\CampusTime;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/** Phase C: Parking Layout slot details, violations and role-based notifications. */
class NotificationsAndLayoutTest extends ParkingTestCase
{
    private function staffTitles(int $staffId): array
    {
        return DB::table('notifications')->where('user_id', $staffId)->orderBy('notification_id')->pluck('title')->all();
    }

    public function test_denied_access_alerts_name_the_problem_and_are_not_duplicated(): void
    {
        $staff = $this->actingAsStaff();
        $suspended = $this->campusVehicle('sedan', 'verified', 'suspended');
        $unverified = $this->campusVehicle('sedan', 'pending_review');
        $visitor = $this->visitorVehicle();
        $legacy = $this->rawPass(['visitor_id' => $visitor['visitor_id'], 'vehicle_id' => $visitor['vehicle_id']]);

        $this->gate('entry', ['rfid_tag_uid' => 'NO-SUCH-TAG']);
        $this->gate('entry', ['rfid_tag_uid' => $suspended['tag']]);
        $this->gate('entry', ['rfid_tag_uid' => $suspended['tag']]); // repeated scan: no second alert
        $this->gate('entry', ['rfid_tag_uid' => $unverified['tag']]);
        $this->gate('entry', ['qr_pass_code' => $legacy]);

        $titles = $this->staffTitles($staff->user_id);
        $this->assertSame(['Unauthorized vehicle', 'Suspended vehicle entry attempt', 'Denied RFID access', 'Denied visitor QR access'], $titles);
    }

    public function test_wrong_slot_report_links_transaction_slots_and_alerts_staff(): void
    {
        $staff = $this->actingAsStaff();
        $car = $this->campusVehicle();
        $in = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $txn = $this->activeTransaction($car['vehicle_id']);

        $id = $this->postJson("/api/parking/transactions/{$txn->transaction_id}/observed-slot", ['slot_code' => 'C-40'])->json('violation_id');

        $v = DB::table('parking_violations')->where('violation_id', $id)->first();
        $this->assertSame($txn->transaction_id, (int) $v->parking_transaction_id);
        $this->assertSame((int) $in['slot_id'], (int) $v->expected_slot_id);
        $this->assertSame((int) DB::table('parking_slots')->where('slot_code', 'C-40')->value('slot_id'), (int) $v->slot_id);
        $this->assertSame($car['user']->user_id, (int) $v->user_id);
        $this->assertContains('Parking Slot Violation', $this->staffTitles($staff->user_id));

        $row = collect($this->getJson('/api/violations')->json('data'))->firstWhere('violation_id', $id);
        $this->assertSame($in['slot_code'], $row['assigned_slot']);
        $this->assertSame('C-40', $row['observed_slot']);
        $this->assertFalse($row['session_ended']);

        // The map marks the incident while the vehicle is parked.
        $this->assertNotNull(collect($this->getJson('/api/parking/map')->json('slots'))->firstWhere('slot_code', 'C-40')['incident']);
    }

    public function test_layout_slot_details_match_entry_and_exit_records(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $in = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);

        $slot = collect($this->getJson('/api/parking/map')->json('slots'))->firstWhere('slot_code', $in['slot_code']);
        $record = $this->getJson('/api/parking/transactions?status=active&search=' . urlencode($car['plate']))->json('data.0');

        $this->assertSame('occupied', $slot['status']);
        $this->assertSame($record['transaction_id'], $slot['occupant']['transaction_id']);
        $this->assertSame($record['plate_number'], $slot['occupant']['plate_number']);
        $this->assertSame($record['vehicle_type'], $slot['occupant']['vehicle_type']);
        $this->assertSame($record['entry_datetime'], $slot['occupant']['entry_datetime']);
        $this->assertSame('rfid', $slot['occupant']['access_method']);
        $this->assertSame('student', $record['user_category']);

        $this->gate('exit', ['rfid_tag_uid' => $car['tag']]);
        $this->assertSame('available', collect($this->getJson('/api/parking/map')->json('slots'))->firstWhere('slot_code', $in['slot_code'])['status']);
    }

    public function test_revoking_a_pass_notifies_the_guest_account(): void
    {
        $this->postJson('/api/guest/register', [
            'full_name' => 'Revoke Guest', 'email' => 'revoke.guest@example.com', 'contact_number' => '09170000010',
            'password' => 'RevokeGuest2026', 'password_confirmation' => 'RevokeGuest2026', 'agree' => true,
        ])->assertCreated();
        $guest = User::where('email', 'revoke.guest@example.com')->first();
        $guest->markEmailAsVerified();
        \Illuminate\Support\Facades\Storage::fake('private');
        Sanctum::actingAs($guest);
        $vehicle = $this->postJson('/api/guest/vehicles', ['plate_number' => 'RVK 1001', 'vehicle_type_id' => DB::table('vehicle_types')->where('type_code', 'sedan')->value('vehicle_type_id')])->json('vehicle_id');
        $now = CampusTime::now();
        $id = $this->post('/api/guest/requests', ['vehicle_id' => $vehicle, 'visit_date' => $now->addDay()->toDateString(), 'arrival_time' => '00:00', 'departure_time' => '23:59', 'purpose' => 'x', 'visiting' => 'y', 'driver_is_requester' => true, 'driver_license_number' => 'N01-23-456789', 'driver_license_expiry' => '2035-12-31', 'passenger_count' => 0, 'driver_license_photo' => \Illuminate\Http\UploadedFile::fake()->image('license.jpg'), 'vehicle_photo' => \Illuminate\Http\UploadedFile::fake()->image('vehicle.jpg')], ['Accept' => 'application/json'])->json('request_id');

        $this->actingAsAdmin();
        $pass = $this->postJson("/api/visitors/requests/{$id}/approve", ['documents_reviewed' => true])->json('pass');
        $this->actingAsStaff();
        $this->postJson("/api/visitors/passes/{$pass['qr_pass_id']}/revoke", ['reason' => 'Event cancelled'])->assertOk();

        $this->assertTrue(DB::table('notifications')->where('user_id', $guest->user_id)->where('notification_type', 'visitor_pass_revoked')->exists());
    }
}
