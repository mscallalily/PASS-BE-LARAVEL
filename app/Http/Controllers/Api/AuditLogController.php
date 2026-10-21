<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportDataset;
use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $filters = $request->validate(['search' => 'nullable|string|max:100', 'date_from' => 'nullable|date_format:Y-m-d', 'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from']);

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
            $query->where('created_at', '>=', CampusTime::stored(CarbonImmutable::parse($filters['date_from'], CampusTime::zone())->startOfDay()));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<', CampusTime::stored(CarbonImmutable::parse($filters['date_to'], CampusTime::zone())->startOfDay()->addDay()));
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(function ($row) {
                foreach (['old_values', 'new_values'] as $field) {
                    if (is_string($row->$field)) {
                        $row->$field = json_decode($row->$field, true);
                    }
                    $row->$field = ReportDataset::redact($row->$field);
                }
                $row->created_at = CarbonImmutable::parse($row->created_at, config('app.timezone'))->toIso8601String();

                return $row;
            });

        return response()->json($logs)->header('Cache-Control', 'private, no-store');
    }
}
