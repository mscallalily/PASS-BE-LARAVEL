<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\AuditPriorityAlerts;
use App\Support\ActivityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AuditPriorityController extends Controller
{
    public function index(Request $r, AuditPriorityAlerts $service)
    {
        abort_unless($r->user()->isAdmin(), 403);
        $alerts = $service->current(); $summary = ['critical' => 0, 'high' => 0, 'medium' => 0];
        foreach ($alerts as $a) if (!$a['review']) $summary[$a['severity']]++;
        return response()->json(['data' => $alerts, 'summary' => $summary, 'generated_at' => now()->toIso8601String(), 'note' => 'Sign-in and scan patterns: last 15 minutes. Physical record changes: last 24 hours. Parking integrity: current records. Up to 50 recent signals per rule. Signals need review and do not establish misconduct.'])->header('Cache-Control', 'private, no-store');
    }

    public function review(Request $r, string $key, AuditPriorityAlerts $service)
    {
        abort_unless($r->user()->isAdmin(), 403);
        $note = trim($r->validate(['note' => ['required', 'string', 'min:5', 'max:500']])['note']);
        abort_if(mb_strlen($note) < 5, 422, 'Enter a review note.');
        $alert = collect($service->current())->firstWhere('key', $key);
        abort_unless($alert, 409, 'This alert has changed or is outside the current review window. Refresh the list.');
        DB::transaction(function () use ($r, $key, $note, $alert) {
            DB::table('audit_alert_reviews')->updateOrInsert(['alert_key' => $key], ['rule' => $alert['rule'], 'severity' => $alert['severity'], 'reviewed_by' => $r->user()->user_id, 'note' => $note, 'reviewed_at' => now()]);
            ActivityAudit::record($r, $r->user(), 'priority_alert_reviewed', 'PriorityAlert', 0, null, ['alert_key' => $key, 'rule' => $alert['rule'], 'note' => $note]);
        }, 3);
        return response()->json(['message' => 'Review recorded. The original evidence remains unchanged.']);
    }
}
