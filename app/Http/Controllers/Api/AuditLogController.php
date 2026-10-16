<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $query = DB::table('audit_logs')
            ->select(
                'log_id',
                'user_id',
                'actor_label',
                'action',
                'auditable_type',
                'auditable_id',
                'old_values',
                'new_values',
                'ip_address',
                'user_agent',
                'created_at'
            );

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));

            $query->where(function ($q) use ($search) {
                $q->where('actor_label', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('auditable_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($logs);
    }
}
