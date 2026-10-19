<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Change markers for the shared frontend refresh. The app polls this small
 * endpoint and reloads a screen's data only when one of the topics it shows
 * has changed, so all open screens stay in step without each one polling its
 * own data. Each topic's value is a short hash of cheap MAX()/COUNT() reads.
 *
 * Staff receive system-wide markers; other accounts only markers for their
 * own records (no counts or ids of other people's data are exposed).
 */
class SyncController extends Controller
{
    public function revision(Request $request): JsonResponse
    {
        $user = $request->user();

        $topics = $user->canManageParking() ? $this->staffTopics($user->user_id) : $this->ownTopics($user);

        return response()->json([
            'topics' => array_map(fn ($parts) => substr(md5(json_encode($parts)), 0, 12), $topics),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function staffTopics(int $userId): array
    {
        return [
            // Gate events, parking transactions (entry, exit, slot changes) and slot status.
            'parking' => [
                DB::table('access_events')->max('event_id'),
                DB::table('parking_transactions')->selectRaw('MAX(transaction_id) m, MAX(updated_at) u, SUM(transaction_status = \'active\') a')->first(),
                DB::table('parking_slots')->max('updated_at'),
            ],
            'visitors' => [
                DB::table('visitor_requests')->selectRaw('MAX(request_id) m, MAX(updated_at) u')->first(),
                DB::table('qr_passes')->selectRaw('MAX(qr_pass_id) m, MAX(updated_at) u')->first(),
            ],
            'violations' => [DB::table('parking_violations')->selectRaw('MAX(violation_id) m, MAX(updated_at) u')->first()],
            'vehicles' => [
                DB::table('vehicle_authorizations')->selectRaw('MAX(authorization_id) m, MAX(updated_at) u')->first(),
                DB::table('driver_licenses')->selectRaw('MAX(license_id) m, MAX(updated_at) u')->first(),
            ],
            'notifications' => [$this->notifications($userId)],
        ];
    }

    private function ownTopics(object $user): array
    {
        $visitorId = DB::table('visitors')->where('user_id', $user->user_id)->value('visitor_id');

        return [
            'parking' => [DB::table('parking_transactions as t')
                ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->where(fn ($q) => $q->where('t.user_id', $user->user_id)->orWhere('v.owner_user_id', $user->user_id)
                    ->when($visitorId, fn ($w) => $w->orWhere('t.visitor_id', $visitorId)))
                ->selectRaw('MAX(t.transaction_id) m, MAX(t.updated_at) u')->first()],
            'visitors' => $visitorId ? [
                DB::table('visitor_requests')->where('visitor_id', $visitorId)->selectRaw('MAX(request_id) m, MAX(updated_at) u')->first(),
                DB::table('qr_passes')->where('visitor_id', $visitorId)->selectRaw('MAX(qr_pass_id) m, MAX(updated_at) u')->first(),
                DB::table('vehicles')->where('owner_visitor_id', $visitorId)->selectRaw('MAX(vehicle_id) m, MAX(updated_at) u')->first(),
            ] : [],
            'notifications' => [$this->notifications($user->user_id)],
        ];
    }

    private function notifications(int $userId): object
    {
        return DB::table('notifications')->where('user_id', $userId)->where('channel', 'in_app')
            ->selectRaw('MAX(notification_id) m, SUM(is_read = 0) unread, MAX(read_at) r')->first();
    }
}
