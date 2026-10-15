<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (
            ! $actor ||
            ! $actor->roles()->whereIn('role_code', [
                'admin',
                'parking_admin',
            ])->exists()
        ) {
            return response()->json([
                'message' => 'You are not authorized to view audit logs.',
            ], 403);
        }

        $logs = AuditLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('log_id')
            ->paginate(20);

        return response()->json($logs);
    }
}
