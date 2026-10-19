<?php

namespace App\Support;

use App\Models\User;
use App\Services\Reports\ReportDataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Append-only activity records. Never include credential secrets or uploaded document paths. */
final class ActivityAudit
{
    public static function record(Request $request, User $actor, string $action, string $type, int $id, ?array $before = null, ?array $after = null): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $actor->user_id, 'actor_label' => $actor->user_code, 'action' => $action,
            'auditable_type' => $type, 'auditable_id' => $id, 'old_values' => $before === null ? null : json_encode(ReportDataset::redact($before)),
            'new_values' => $after === null ? null : json_encode(ReportDataset::redact($after)), 'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 252), 'created_at' => now(),
        ]);
    }
}
