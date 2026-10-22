<?php

namespace Tests\Feature\Parking;

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

    public function test_vehicle_ocr_routes_are_removed_for_guests_users_and_staff(): void
    {
        $before = DB::table('vehicles')->count();
        $this->postJson('/api/vehicles/read-photo')->assertNotFound();
        foreach (['student', 'other', 'security', 'admin'] as $type) {
            Sanctum::actingAs($this->user($type));
            $this->postJson('/api/vehicles/read-photo')->assertNotFound();
            $this->postJson('/api/vehicles/123/plate-check')->assertNotFound();
        }
        $this->assertDatabaseCount('vehicles', $before);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }
}
