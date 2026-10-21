<?php

namespace Tests\Feature\Parking;

use App\Services\Vehicles\VehiclePlateReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class PhotoReadAndLicenseThrottleTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('private');
    }

    private function regular()
    {
        $user = $this->user();
        $user->roles()->attach(DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_live_polling_does_not_use_license_submission_or_recheck_budget(): void
    {
        $this->regular();
        for ($i = 0; $i < 25; $i++) {
            $this->getJson('/api/sync/revision')->assertOk();
        }
        $this->postJson('/api/licenses', [])->assertUnprocessable();
        $this->postJson('/api/licenses/999999/recheck')->assertForbidden();
    }

    public function test_license_submit_limit_is_per_user_and_has_retry_after_without_blocking_status_or_recheck(): void
    {
        $limitedUser = $this->regular();
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/licenses', [])->assertUnprocessable();
        }
        $this->postJson('/api/licenses', [])->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('code', 'rate_limited')->assertJsonPath('action', 'license-submit');
        $this->getJson('/api/licenses/mine')->assertOk();
        $this->postJson('/api/licenses/999999/recheck')->assertForbidden();
        $this->regular();
        $this->postJson('/api/licenses', [])->assertUnprocessable();
        $this->travel(61)->seconds();
        Sanctum::actingAs($limitedUser->fresh());
        $this->postJson('/api/licenses', [])->assertUnprocessable();
    }

    public function test_photo_read_requires_active_allowed_account_and_never_stores_photo_or_creates_vehicle(): void
    {
        $this->postJson('/api/vehicles/read-photo')->assertUnauthorized();
        $this->regular();
        $before = DB::table('vehicles')->count();
        $this->mock(VehiclePlateReader::class)->shouldReceive('read')->once()->andReturn(['status' => 'suggested', 'plate_number' => 'ABC4321', 'candidates' => [['plate' => 'ABC4321', 'confidence' => 95]], 'message' => 'Review suggestion.']);
        $this->post('/api/vehicles/read-photo', ['vehicle_photo' => UploadedFile::fake()->image('car.jpg', 1200, 800)], ['Accept' => 'application/json'])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.plate_number', 'ABC4321');
        $this->assertDatabaseCount('vehicles', $before);
        $this->assertSame([], Storage::disk('private')->allFiles());
        $this->actingAsAdmin();
        $this->postJson('/api/vehicles/read-photo', [])->assertForbidden();
        $user = $this->regular();
        $user->forceFill(['account_status' => 'pending'])->save();
        Sanctum::actingAs($user->fresh());
        $this->postJson('/api/vehicles/read-photo', [])->assertForbidden();
    }

    public function test_photo_read_validation_limits_are_separate_from_polling_and_license_submit(): void
    {
        $this->regular();
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/vehicles/read-photo', [])->assertUnprocessable();
        }
        $this->postJson('/api/vehicles/read-photo', [])->assertStatus(429)->assertJsonPath('action', 'vehicle-photo-read');
        $this->postJson('/api/licenses', [])->assertUnprocessable();
        $this->getJson('/api/sync/revision')->assertOk();
    }

    public function test_guest_can_read_a_photo_but_invalid_files_and_excess_dimensions_are_rejected(): void
    {
        $user = $this->user('other');
        $user->roles()->attach(DB::table('roles')->where('role_code', 'guest')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($user);
        $this->mock(VehiclePlateReader::class)->shouldReceive('read')->once()->andReturn(['status' => 'review_needed', 'plate_number' => null, 'candidates' => [], 'message' => 'Read manually.']);
        $this->post('/api/vehicles/read-photo', ['vehicle_photo' => UploadedFile::fake()->image('guest.jpg')], ['Accept' => 'application/json'])->assertOk();
        $this->post('/api/vehicles/read-photo', ['vehicle_photo' => UploadedFile::fake()->create('not-image.pdf', 5, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post('/api/vehicles/read-photo', ['vehicle_photo' => UploadedFile::fake()->image('too-large.jpg')->size(5121)], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post('/api/vehicles/read-photo', ['vehicle_photo' => UploadedFile::fake()->image('too-many-pixels.jpg', 6000, 4000)], ['Accept' => 'application/json'])->assertUnprocessable();
    }
}
