<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/** Explainable operational signals, never an automatic finding of misconduct. */
final class AuditPriorityAlerts
{
    public function current(): array
    {
        $alerts = [];
        $add = function ($rule, $group, $occurrence, $severity, $title, $detail, $source, $id, $at, $count = 1) use (&$alerts) {
            $alerts[] = ['key' => hash('sha256', "$rule|$group|$occurrence"), 'rule' => $rule, 'severity' => $severity, 'title' => $title, 'detail' => $detail, 'source' => $source, 'record_id' => (string) $id, 'occurred_at' => \Carbon\Carbon::parse($at, config('app.timezone'))->toIso8601String(), 'count' => (int) $count];
        };
        $failures = DB::table('login_attempts')->where('was_successful', false)->where('attempted_at', '>=', now()->subMinutes(15))->groupBy('identifier', 'ip_address')
            ->selectRaw('identifier, ip_address, COUNT(*) as n, MAX(attempt_id) as last_id, MAX(attempted_at) as last_at')->havingRaw('COUNT(*) >= 5')->orderByDesc('last_id')->limit(50)->get();
        foreach ($failures as $f) $add('repeated_signin', $f->identifier.'|'.$f->ip_address, $f->last_id, $f->n >= 10 ? 'critical' : 'high', 'Repeated unsuccessful sign-ins', "$f->n unsuccessful attempts for the same sign-in identifier and network address in 15 minutes. Check for typing errors or unauthorized attempts.", 'login_attempts', $f->last_id, $f->last_at, $f->n);
        $fingerprint = "SHA2(CONCAT_WS('|', gate_id, access_method, COALESCE(CAST(rfid_id AS CHAR), JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.credential')), CAST(qr_pass_id AS CHAR), 'unknown')), 256)";
        $denials = DB::table('access_events')->where('auth_result', 'denied')->where('event_datetime', '>=', now()->subMinutes(15))->selectRaw("$fingerprint as fingerprint, COUNT(*) as n, MAX(event_id) as last_id, MAX(event_datetime) as last_at")
            ->groupByRaw($fingerprint)->havingRaw('COUNT(*) >= 3')->orderByDesc('last_id')->limit(50)->get();
        foreach ($denials as $d) $add('repeated_denial', $d->fingerprint, $d->last_id, $d->n >= 10 ? 'high' : 'medium', 'Repeated denied access scans', "$d->n denied scans at the same gate for the same credential in 15 minutes. A reader repeatedly seeing one tag can also cause this signal.", 'access_events', $d->last_id, $d->last_at, $d->n);
        foreach (['vehicle_id' => 'vehicle', 'slot_id' => 'slot'] as $column => $label) {
            $rows = DB::table('parking_transactions')->where('transaction_status', 'active')->whereNotNull($column)->groupBy($column)->selectRaw("$column as group_id, COUNT(*) as n, MAX(transaction_id) as last_id, MAX(entry_datetime) as last_at")->havingRaw('COUNT(*) > 1')->limit(50)->get();
            foreach ($rows as $d) $add('duplicate_active_'.$label, $d->group_id, $d->last_id, 'critical', 'Conflicting active parking records', "More than one active parking transaction references the same $label. Reconcile the records before assigning another space.", 'parking_transactions', $d->last_id, $d->last_at, $d->n);
        }
        foreach (DB::table('parking_transactions')->where('transaction_status', 'active')->where(fn ($q) => $q->whereNull('slot_id')->orWhereNotNull('exit_datetime'))->orderByDesc('transaction_id')->limit(50)->get() as $d)
            $add('active_stay_integrity', $d->transaction_id, $d->transaction_id, 'critical', 'Active parking record needs reconciliation', 'An active transaction has no assigned slot or already contains an exit time. Compare it with gate and occupancy records.', 'parking_transactions', $d->transaction_id, $d->entry_datetime);
        foreach (DB::table('parking_transactions')->where('transaction_status', 'active')->where('entry_datetime', '<', now()->subHours(24))->orderByDesc('transaction_id')->limit(50)->get() as $d)
            $add('extended_stay', $d->transaction_id, $d->transaction_id, 'medium', 'Vehicle has no recorded exit after 24 hours', 'Confirm the vehicle is still present. An approved overnight stay or missed scan can explain this signal.', 'parking_transactions', $d->transaction_id, $d->entry_datetime);
        foreach (DB::table('parking_transactions')->whereIn('transaction_status', ['completed', 'force_closed'])->whereNull('exit_datetime')->orderByDesc('transaction_id')->limit(50)->get() as $d)
            $add('missing_exit_time', $d->transaction_id, $d->transaction_id, 'high', 'Closed parking record has no exit time', 'Review the entry and exit evidence. A missing exit time makes the recorded duration incomplete.', 'parking_transactions', $d->transaction_id, $d->entry_datetime);
        foreach (DB::table('record_history')->where('recorded_at', '>=', now()->subDay())->where(fn ($q) => $q->where('operation', 'deleted')->orWhere(fn ($q) => $q->where('record_type', 'audit_logs')->where('operation', 'updated')))->orderByDesc('revision_id')->limit(50)->get() as $d)
            $add('physical_record_change', $d->record_type.':'.$d->record_id, $d->revision_id, $d->record_type === 'audit_logs' ? 'critical' : 'high', 'Historical record was physically changed or removed', 'Review the retained revision and its source. Normal archive actions do not trigger this signal.', 'record_history', $d->revision_id, $d->recorded_at);
        $reviews = DB::table('audit_alert_reviews')->whereIn('alert_key', array_column($alerts, 'key'))->get()->keyBy('alert_key');
        foreach ($alerts as &$a) { $r = $reviews[$a['key']] ?? null; $a['review'] = $r ? ['reviewed_by' => $r->reviewed_by, 'note' => $r->note, 'reviewed_at' => $r->reviewed_at] : null; }
        unset($a);
        $rank = ['critical' => 0, 'high' => 1, 'medium' => 2];
        usort($alerts, fn ($a, $b) => [(int) (bool) $a['review'], $rank[$a['severity']], -strtotime($a['occurred_at'])] <=> [(int) (bool) $b['review'], $rank[$b['severity']], -strtotime($b['occurred_at'])]);
        return $alerts;
    }
}
