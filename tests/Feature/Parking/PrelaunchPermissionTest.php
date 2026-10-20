<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

class PrelaunchPermissionTest extends ParkingTestCase
{
    public function test_every_authenticated_api_route_rejects_anonymous_requests(): void
    {
        $count = 0;
        foreach (Route::getRoutes() as $route) {
            if (!str_starts_with($route->uri(), 'api/') || !in_array('auth:sanctum', $route->gatherMiddleware(), true)) continue;
            $uri = '/'.preg_replace('/\{[^}]+\}/', '1', str_replace('{kind}', 'license', $route->uri()));
            $method = $route->methods()[0];
            $response = $this->json($method, $uri);
            $this->assertSame(401, $response->status(), $method.' '.$uri);
            $this->assertTrue(is_callable($route->getAction('uses')) || class_exists($route->getControllerClass()), $route->uri());
            $controller = $route->getAction('controller');
            if (is_string($controller) && str_contains($controller, '@')) {
                [$class, $handler] = explode('@', $controller, 2);
                $this->assertTrue(method_exists($class, $handler), $controller);
            }
            $count++;
        }
        $this->assertGreaterThan(60, $count);
    }

    public function test_regular_user_cannot_reach_privileged_records_or_mutations(): void
    {
        Sanctum::actingAs($this->user());
        foreach (['/api/admin/users', '/api/admin/reports', '/api/admin/dashboard', '/api/admin/audit-logs', '/api/admin/system-settings',
            '/api/parking/transactions', '/api/parking/overview', '/api/visitors/requests', '/api/access-events', '/api/rfid/assignments'] as $uri) {
            $this->getJson($uri)->assertForbidden();
        }
        $this->postJson('/api/admin/users', [])->assertForbidden();
        $this->postJson('/api/rfid/assignments', [])->assertForbidden();
        $this->postJson('/api/parking/entry', [])->assertForbidden();
        $this->postJson('/api/parking/exit', [])->assertForbidden();
    }

    public function test_foreign_private_document_and_vehicle_selection_are_denied(): void
    {
        $other = $this->campusVehicle();
        $user = $this->user();
        $user->roles()->attach(\Illuminate\Support\Facades\DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($user);
        $this->getJson('/api/vehicles/'.$other['vehicle_id'].'/photo')->assertForbidden();
        $this->postJson('/api/vehicles/'.$other['vehicle_id'].'/select-for-use')->assertNotFound();
        $this->getJson('/api/admin/users/'.$other['user']->user_id.'/school-id')->assertForbidden();
        $license = \Illuminate\Support\Facades\DB::table('driver_licenses')->where('driver_id', $other['driver_id'])->value('license_id');
        \Illuminate\Support\Facades\DB::table('driver_licenses')->where('license_id', $license)->update(['license_image_path' => 'audit/private-license.jpg']);
        $this->getJson('/api/licenses/'.$license.'/file')->assertForbidden();
    }

    public function test_forged_bearer_token_cannot_restore_a_session(): void
    {
        $this->withToken('999999|not-a-real-token')->getJson('/api/me')->assertUnauthorized();
    }
}
