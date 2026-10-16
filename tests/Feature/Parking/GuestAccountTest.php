<?php

namespace Tests\Feature\Parking;

use App\Models\User;
use App\Support\CampusTime;
use Database\Seeders\GuestDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use App\Notifications\PassEmailVerificationNotification;
use Laravel\Sanctum\Sanctum;

class GuestAccountTest extends ParkingTestCase
{
    private function postGuestRequest(array $data)
    {
        return $this->post('/api/guest/requests', $data, ['Accept' => 'application/json']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        \Illuminate\Support\Facades\Storage::fake('private');
    }

    private function registration(array $overrides = []): array
    {
        return $overrides + [
            'full_name' => 'Ana Maria Cruz',
            'email' => 'ana.' . random_int(1000, 99999) . '@example.com',
            'contact_number' => '0917 123 4567',
            'password' => 'Visitor2026pass',
            'password_confirmation' => 'Visitor2026pass',
            'agree' => true,
        ];
    }

    /** Register through the API and sign in as that guest. */
    private function guest(): User
    {
        $data = $this->registration();
        $this->postJson('/api/guest/register', $data)->assertCreated();
        $user = User::where('email', $data['email'])->firstOrFail();
        $user->markEmailAsVerified();
        Sanctum::actingAs($user);

        return $user;
    }

    private function guestVehicle(): int
    {
        return $this->postJson('/api/guest/vehicles', [
            'plate_number' => 'gst ' . random_int(1000, 9999),
            'vehicle_type_id' => DB::table('vehicle_types')->where('type_code', 'sedan')->value('vehicle_type_id'),
        ])->assertCreated()->json('vehicle_id');
    }

    private function requestData(int $vehicleId, int $hours = 4): array
    {
        return [
            'vehicle_id' => $vehicleId,
            'visit_date' => CampusTime::now()->addDay()->toDateString(),
            'arrival_time' => '10:00',
            'departure_time' => sprintf('%02d:00', 10 + $hours),
            'purpose' => 'Meeting', 'visiting' => 'Registrar',
            'driver_license_photo' => \Illuminate\Http\UploadedFile::fake()->image('license.jpg'),
            'vehicle_photo' => \Illuminate\Http\UploadedFile::fake()->image('vehicle.jpg'),
            'driver_is_requester' => true,
            'driver_license_number' => 'N01-23-456789',
            'driver_license_expiry' => '2035-12-31',
            'passenger_count' => 2,
        ];
    }

    private function requestFuture(int $vehicleId, int $hours = 4): int
    {
        return $this->postGuestRequest($this->requestData($vehicleId, $hours))->assertCreated()->json('request_id');
    }

    public function test_same_day_past_dates_and_invalid_driver_details_are_rejected(): void
    {
        $this->guest();
        $vehicle = $this->guestVehicle();
        $payload = $this->requestData($vehicle);
        $this->postGuestRequest(array_replace($payload, ['visit_date' => CampusTime::date()]))->assertUnprocessable()->assertJsonValidationErrors('visit_date');
        $this->postGuestRequest(array_replace($payload, ['visit_date' => CampusTime::now()->subDay()->toDateString()]))->assertUnprocessable()->assertJsonValidationErrors('visit_date');
        $this->postGuestRequest(array_replace($payload, ['departure_time' => '09:00']))->assertUnprocessable()->assertJsonValidationErrors('departure_time');
        $this->postGuestRequest(array_replace($payload, ['driver_is_requester' => false, 'driver_name' => '']))->assertUnprocessable()->assertJsonValidationErrors('driver_name');
        $this->postGuestRequest(array_replace($payload, ['driver_license_expiry' => CampusTime::now()->subDay()->toDateString()]))->assertUnprocessable()->assertJsonValidationErrors('driver_license_expiry');
        $this->postGuestRequest(array_replace($payload, ['passenger_count' => -1]))->assertUnprocessable()->assertJsonValidationErrors('passenger_count');
        $this->assertSame(0, DB::table('visitor_requests')->count());
    }

    public function test_separate_driver_is_saved_encrypted_and_visible_as_declared_details_to_admin(): void
    {
        $this->guest();
        $payload = array_replace($this->requestData($this->guestVehicle()), ['driver_is_requester' => false, 'driver_name' => 'Juan Santos', 'passenger_count' => 3]);
        $id = $this->postGuestRequest($payload)->assertCreated()->json('request_id');
        $stored = DB::table('visitor_requests')->where('request_id', $id)->first();
        $this->assertNotSame($payload['driver_license_number'], $stored->driver_license_encrypted);
        $this->getJson('/api/guest/requests')->assertOk()->assertJsonPath('data.0.driver.name', 'Juan Santos')->assertJsonPath('data.0.driver.license_reference', '•••• 6789')->assertJsonPath('data.0.driver.passenger_count', 3)->assertJsonPath('data.0.driver.identity_verified', false);
        $this->actingAsAdmin();
        $this->getJson('/api/visitors/requests')->assertOk()->assertJsonPath('data.0.driver.name', 'Juan Santos');
    }

    public function test_tomorrow_means_next_manila_calendar_day_even_near_midnight(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-16 23:55:00', 'Asia/Manila'));
        $this->guest();
        $payload = $this->requestData($this->guestVehicle());
        $payload['arrival_time'] = '00:10';
        $payload['departure_time'] = '01:00';
        $this->postGuestRequest($payload)->assertCreated();
        $this->getJson('/api/guest/requests')->assertJsonPath('data.0.driver.name', 'Ana Maria Cruz');
    }

    public function test_photos_are_required_private_and_scoped_to_the_request_owner(): void
    {
        // Rate limiting has a dedicated test; isolate repeated invalid uploads and account fixtures here.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $guest = $this->guest();
        $payload = $this->requestData($this->guestVehicle());
        $missing = $payload;
        unset($missing['driver_license_photo'], $missing['vehicle_photo']);
        $this->postGuestRequest($missing)->assertUnprocessable()->assertJsonValidationErrors(['driver_license_photo', 'vehicle_photo']);
        $this->postGuestRequest(array_replace($payload, ['driver_license_photo' => \Illuminate\Http\UploadedFile::fake()->create('bad.pdf', 10, 'application/pdf')]))->assertUnprocessable()->assertJsonValidationErrors('driver_license_photo');
        $this->postGuestRequest(array_replace($payload, ['vehicle_photo' => \Illuminate\Http\UploadedFile::fake()->image('large.jpg')->size(5121)]))->assertUnprocessable()->assertJsonValidationErrors('vehicle_photo');
        $id = $this->postGuestRequest($payload)->assertCreated()->json('request_id');
        $row = DB::table('visitor_requests')->where('request_id', $id)->first();
        \Illuminate\Support\Facades\Storage::disk('private')->assertExists($row->driver_license_photo_path);
        $this->get('/api/visitors/requests/' . $id . '/documents/license', ['Accept' => 'application/json'])->assertOk();
        $this->guest();
        $this->getJson('/api/visitors/requests/' . $id . '/documents/license')->assertForbidden();
        \Laravel\Sanctum\Sanctum::actingAs($this->user('student'));
        $this->getJson('/api/visitors/requests/' . $id . '/documents/license')->assertForbidden();
        $this->actingAsStaff();
        $this->getJson('/api/visitors/requests/' . $id . '/documents/license')->assertOk();
        $this->actingAsAdmin();
        $this->getJson('/api/visitors/requests/' . $id . '/documents/vehicle')->assertOk();
        $this->getJson('/api/visitors/requests/' . $id . '/documents/unknown')->assertNotFound();
    }

    public function test_approval_requires_stored_photos_and_explicit_document_review(): void
    {
        $this->guest();
        $id = $this->requestFuture($this->guestVehicle());
        $this->actingAsAdmin();
        $this->postJson("/api/visitors/requests/{$id}/approve")->assertUnprocessable()->assertJsonValidationErrors('documents_reviewed');
        $this->assertFalse(DB::table('qr_passes')->where('request_id', $id)->exists());
        $this->postJson("/api/visitors/requests/{$id}/approve", ['documents_reviewed' => true])->assertOk();
        $this->assertNotNull(DB::table('visitor_requests')->where('request_id', $id)->value('documents_reviewed_at'));
        $this->guest();
        $missingId = $this->requestFuture($this->guestVehicle());
        $path = DB::table('visitor_requests')->where('request_id', $missingId)->value('vehicle_photo_path');
        \Illuminate\Support\Facades\Storage::disk('private')->delete($path);
        $this->actingAsAdmin();
        $this->postJson("/api/visitors/requests/{$missingId}/approve", ['documents_reviewed' => true])->assertStatus(409);
        $this->assertFalse(DB::table('qr_passes')->where('request_id', $missingId)->exists());
    }

    public function test_rejected_duplicate_submission_does_not_leave_orphan_photos(): void
    {
        $this->guest();
        $payload = $this->requestData($this->guestVehicle());
        $this->postGuestRequest($payload)->assertCreated();
        $before = \Illuminate\Support\Facades\Storage::disk('private')->allFiles();
        $this->postGuestRequest($payload)->assertStatus(409);
        $this->assertSame($before, \Illuminate\Support\Facades\Storage::disk('private')->allFiles());
    }

    public function test_registration_creates_account_role_profile_and_linked_visitor(): void
    {
        $data = $this->registration();

        $this->postJson('/api/guest/register', $data)->assertCreated();

        $user = DB::table('users')->where('email', $data['email'])->first();
        $this->assertSame('other', $user->user_type);
        $this->assertSame('active', $user->account_status);
        $this->assertNotSame($data['password'], $user->password);
        $this->assertTrue(Hash::check($data['password'], $user->password));
        $this->assertTrue(User::find($user->user_id)->isGuest());
        $this->assertSame('Ana Maria', DB::table('user_profiles')->where('user_id', $user->user_id)->value('first_name'));
        $visitor = DB::table('visitors')->where('user_id', $user->user_id)->first();
        $this->assertSame('Cruz', $visitor->last_name);
        $this->assertSame('09171234567', $visitor->contact_number);

        $model = User::findOrFail($user->user_id);
        Notification::assertSentTo($model, PassEmailVerificationNotification::class);
        $this->postJson('/api/login', ['identifier' => $data['email'], 'password' => $data['password']])
            ->assertForbidden()->assertJsonPath('code', 'email_unverified');
        $this->assertSame(0, $model->tokens()->count());
        Sanctum::actingAs($model);
        $this->getJson('/api/guest/profile')->assertForbidden();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $model->getKey(), 'hash' => sha1($model->getEmailForVerification()),
        ]);
        $this->getJson($url)->assertOk()->assertJsonPath('message', 'Email verified successfully. You can now sign in and submit a visitor parking request. A QR pass requires request approval.');
        $this->assertNotNull($model->fresh()->email_verified_at);
        $this->assertSame(0, DB::table('qr_passes')->count());
        // Verification allows login but does not issue a QR pass.
        $login = $this->postJson('/api/login', ['identifier' => $data['email'], 'password' => $data['password']])->assertOk();
        $this->assertSame('guest', $login->json('user.role'));
        $this->assertSame('Ana Maria Cruz', $login->json('user.name'));
    }

    public function test_existing_guest_can_resend_and_expired_or_tampered_links_do_not_verify(): void
    {
        $data = $this->registration();
        $this->postJson('/api/guest/register', $data)->assertCreated();
        $user = User::where('email', $data['email'])->firstOrFail();
        Notification::fake();
        $this->travel(61)->seconds();
        $this->postJson('/api/email/verification-notification', ['email' => $data['email']])->assertOk();
        Notification::assertSentTo($user, PassEmailVerificationNotification::class);
        $parameters = ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())];
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), $parameters);
        $this->getJson($expired)->assertForbidden();
        $tampered = URL::temporarySignedRoute('verification.verify', now()->addHour(), $parameters);
        $this->getJson($tampered . 'x')->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_mail_failure_preserves_account_and_allows_resend(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $data = $this->registration();
        $this->postJson('/api/guest/register', $data)->assertCreated()->assertJsonPath('email_verification_sent', false);
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::fake();
        $this->travel(61)->seconds();
        $this->postJson('/api/email/verification-notification', ['email' => $data['email']])->assertOk();
        Notification::assertSentTo($user, PassEmailVerificationNotification::class);
    }

    public function test_registration_validation_and_duplicates(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $data = $this->registration();
        $this->postJson('/api/guest/register', $data)->assertCreated();

        $this->postJson('/api/guest/register', $this->registration(['email' => strtoupper($data['email'])]))->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/guest/register', $this->registration(['password_confirmation' => 'Different2026']))->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/api/guest/register', $this->registration(['contact_number' => '12345']))->assertUnprocessable()->assertJsonValidationErrors('contact_number');
        $this->postJson('/api/guest/register', $this->registration(['full_name' => 'Madonna']))->assertUnprocessable()->assertJsonValidationErrors('full_name');
        $this->postJson('/api/guest/register', $this->registration(['agree' => false]))->assertUnprocessable()->assertJsonValidationErrors('agree');
        $this->postJson('/api/guest/register', $this->registration(['password' => 'short', 'password_confirmation' => 'short']))->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/guest/register', $this->registration(['contact_number' => 'bad']))->assertUnprocessable();
        }
        $this->postJson('/api/guest/register', $this->registration())->assertStatus(429);
    }

    public function test_registration_is_all_or_nothing(): void
    {
        DB::table('roles')->where('role_code', 'guest')->update(['is_active' => false]);
        $users = DB::table('users')->count();
        $visitors = DB::table('visitors')->count();

        $this->postJson('/api/guest/register', $this->registration())->assertStatus(503);

        $this->assertSame($users, DB::table('users')->count());
        $this->assertSame($visitors, DB::table('visitors')->count());
    }

    public function test_guest_cannot_use_staff_or_admin_endpoints(): void
    {
        $this->guest();

        $this->getJson('/api/parking/dashboard')->assertForbidden();
        $this->getJson('/api/visitors/requests')->assertForbidden();
        $this->getJson('/api/access-events')->assertForbidden();
        $this->postJson('/api/parking/entry', ['plate_number' => 'X 1'])->assertForbidden();
        $this->postJson('/api/visitors/requests/1/approve')->assertForbidden();
    }

    public function test_guest_endpoints_reject_non_guests(): void
    {
        $this->actingAsStaff();
        $this->getJson('/api/guest/requests')->assertForbidden();
    }

    public function test_guest_sees_and_uses_only_their_own_records(): void
    {
        $this->guest();
        $otherVehicle = $this->guestVehicle();
        $otherRequest = $this->requestFuture($otherVehicle);

        $this->guest();
        $this->assertSame([], $this->getJson('/api/guest/vehicles')->json('data'));
        $this->assertSame([], $this->getJson('/api/guest/requests')->json('data'));
        $this->postGuestRequest([
            ...$this->requestData($otherVehicle),
        ])->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
        $this->postJson("/api/guest/requests/{$otherRequest}/cancel")->assertNotFound();
    }

    public function test_request_is_pending_without_a_pass_and_admins_are_notified(): void
    {
        $admin = $this->user('admin');
        $this->guest();
        $vehicle = $this->guestVehicle();

        $id = $this->requestFuture($vehicle);

        $this->assertSame('pending', DB::table('visitor_requests')->where('request_id', $id)->value('request_status'));
        $this->assertFalse(DB::table('qr_passes')->where('request_id', $id)->exists());
        $this->assertSame([], $this->getJson('/api/guest/passes')->json('data'));
        $this->assertTrue(DB::table('notifications')->where('user_id', $admin->user_id)->where('notification_type', 'visitor_request_submitted')->where('related_id', $id)->exists());
        $this->assertSame('pending', $this->getJson('/api/guest/requests')->json('data.0.request_status'));
    }

    public function test_full_flow_admin_approval_pass_entry_and_exit(): void
    {
        $guest = $this->guest();
        $vehicle = $this->guestVehicle();
        $id = $this->requestFuture($vehicle);

        $this->actingAsStaff();
        $this->postJson("/api/visitors/requests/{$id}/approve", ['documents_reviewed' => true])->assertForbidden();

        $this->actingAsAdmin();
        $this->postJson("/api/visitors/requests/{$id}/approve", ['documents_reviewed' => true])->assertOk();

        Sanctum::actingAs($guest);
        $this->assertSame('scheduled', $this->getJson('/api/guest/passes')->json('data.0.status'));
        $scheduledCode = $this->getJson('/api/guest/passes')->json('data.0.pass_code');
        $this->actingAsStaff();
        $this->assertSame('denied', $this->gate('entry', ['qr_pass_code' => $scheduledCode])['status']);
        // Scheduled passes become active on the requested visit date.
        $this->travelTo(CampusTime::now()->addDay()->startOfDay()->setHour(10)->addMinute());
        // The guest is notified and sees one active pass linked to the request.
        Sanctum::actingAs($guest);
        $this->assertTrue(DB::table('notifications')->where('user_id', $guest->user_id)->where('notification_type', 'visitor_request_approved')->exists());
        $passes = $this->getJson('/api/guest/passes')->json('data');
        $this->assertCount(1, $passes);
        $this->assertSame('active', $passes[0]['status']);
        $this->assertSame($id, $passes[0]['request_id']);
        $code = $passes[0]['pass_code'];

        // Entry and exit with the same pass at the gate (real ParkingAccessService).
        $this->actingAsStaff();
        $in = $this->gate('entry', ['qr_pass_code' => $code]);
        $this->assertSame('granted', $in['status']);
        $this->assertNotNull($this->activeTransaction($vehicle));
        $this->assertSame('completed', $this->gate('exit', ['qr_pass_code' => $code])['status']);
        $this->assertNull($this->activeTransaction($vehicle));
        $this->assertSame('denied', $this->gate('entry', ['qr_pass_code' => $code])['status']);

        Sanctum::actingAs($guest);
        $visits = $this->getJson('/api/guest/visits')->json('data');
        $this->assertCount(1, $visits);
        $this->assertSame('completed', $visits[0]['status']);
        $this->assertSame('used', $this->getJson('/api/guest/passes')->json('data.0.status'));
    }

    public function test_admin_rejection_notifies_guest_and_issues_no_pass(): void
    {
        $guest = $this->guest();
        $id = $this->requestFuture($this->guestVehicle());

        $this->actingAsAdmin();
        $this->postJson("/api/visitors/requests/{$id}/reject", ['remarks' => 'Office closed that day'])->assertOk();

        Sanctum::actingAs($guest);
        $request = $this->getJson('/api/guest/requests')->json('data.0');
        $this->assertSame('rejected', $request['request_status']);
        $this->assertSame('Office closed that day', $request['review_remarks']);
        $this->assertNull($request['pass']);
        $this->assertTrue(DB::table('notifications')->where('user_id', $guest->user_id)->where('notification_type', 'visitor_request_rejected')->exists());
    }

    public function test_request_whose_visit_has_ended_cannot_be_approved_and_expired_pass_is_denied(): void
    {
        $this->guest();
        $late = $this->requestFuture($this->guestVehicle(), 1);
        $vehicle2 = $this->guestVehicle();
        $approved = $this->requestFuture($vehicle2, 1);

        $this->actingAsAdmin();
        $pass = $this->postJson("/api/visitors/requests/{$approved}/approve", ['documents_reviewed' => true])->assertOk()->json('pass');

        $this->travel(2)->days();
        $this->postJson("/api/visitors/requests/{$late}/approve", ['documents_reviewed' => true])->assertStatus(409);
        $r = $this->gate('entry', ['qr_pass_code' => $pass['pass_code']]);
        $this->assertSame('denied', $r['status']);
        $this->assertSame('Visitor QR pass has expired.', $r['reason']);
    }

    public function test_guest_can_update_profile_and_cancel_a_pending_request(): void
    {
        $guest = $this->guest();
        $id = $this->requestFuture($this->guestVehicle());

        $this->patchJson('/api/guest/profile', ['full_name' => 'Ana Reyes', 'contact_number' => '09998887777', 'affiliation' => 'ACME'])->assertOk();
        $this->assertSame('Reyes', DB::table('visitors')->where('user_id', $guest->user_id)->value('last_name'));

        $this->postJson("/api/guest/requests/{$id}/cancel")->assertOk();
        $this->assertSame('cancelled', DB::table('visitor_requests')->where('request_id', $id)->value('request_status'));
    }

    public function test_demo_seeder_is_repeatable(): void
    {
        $this->seed(GuestDemoSeeder::class);
        $counts = fn () => [DB::table('users')->count(), DB::table('visitors')->count(), DB::table('vehicles')->count(), DB::table('visitor_requests')->count(), DB::table('qr_passes')->count()];
        $first = $counts();
        $this->seed(GuestDemoSeeder::class);

        $this->assertSame($first, $counts());
        $user = User::where('email', 'guest.demo@pass.test')->firstOrFail();
        $this->assertTrue(Hash::check('GuestDemo@2026!', $user->password));
        $visitor = DB::table('visitors')->where('user_id', $user->user_id)->first();
        $this->assertSame('pending', DB::table('visitor_requests')->where('visitor_id', $visitor->visitor_id)->value('request_status'));
        $this->assertFalse(DB::table('qr_passes')->where('visitor_id', $visitor->visitor_id)->exists());
    }
}
