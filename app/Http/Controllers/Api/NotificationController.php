<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The signed-in user's own in-app notifications.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        $rows = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('channel', 'in_app')
            ->orderByDesc('created_at')
            ->orderByDesc('notification_id')
            ->limit(100)
            ->get(['notification_id', 'notification_type', 'title', 'body', 'payload', 'related_type', 'related_id', 'is_read', 'read_at', 'created_at'])
            ->map(function ($row) {
                $row->is_read = (bool) $row->is_read;
                $row->payload = $row->payload ? json_decode($row->payload, true) : null;
                $row->created_at = ParkingOperationsController::iso($row->created_at);
                $row->read_at = ParkingOperationsController::iso($row->read_at);
                return $row;
            });

        return response()->json([
            'data' => $rows,
            'unread_count' => DB::table('notifications')->where('user_id', $userId)->where('channel', 'in_app')->where('is_read', false)->count(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => DB::table('notifications')->where('user_id', $request->user()->user_id)->where('channel', 'in_app')->where('is_read', false)->count(),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $updated = DB::table('notifications')
            ->where('notification_id', $id)
            ->where('user_id', $request->user()->user_id)
            ->update(['is_read' => true, 'read_at' => now(), 'updated_at' => now()]);

        return $updated
            ? response()->json(['message' => 'Notification marked as read.'])
            : response()->json(['message' => 'Notification not found.'], 404);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = DB::table('notifications')
            ->where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => "{$count} notification(s) marked as read."]);
    }
}
