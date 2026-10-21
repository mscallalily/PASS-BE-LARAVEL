<?php

namespace App\Services\Reports;

use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Read-only report datasets. No credential secrets, uploaded files or full license numbers are selected. */
final class ReportDataset
{
    private const FIELDS = ['record_id', 'occurred_at', 'vehicle_id', 'plate_number', 'vehicle_type', 'person', 'user_code', 'user_type', 'status', 'method', 'direction', 'credential', 'entry_at', 'exit_at', 'duration_minutes', 'area', 'parking_area_id', 'slot_id', 'details', 'old_values', 'new_values'];

    public static function filterKeys(string $category, string $dataset = 'scans'): array
    {
        return match ($category) {
            'users', 'audit' => ['search', 'user_type', 'status'],
            'rfid' => $dataset === 'devices' ? ['search', 'status', 'area_id'] : ['search', 'vehicle_type', 'user_type', 'status'],
            'vehicles', 'visitors' => ['search', 'vehicle_type', 'user_type', 'status'],
            default => ['search', 'vehicle_type', 'user_type', 'status', 'area_id', 'method'],
        };
    }

    public static function statuses(string $category, string $dataset = 'scans'): array
    {
        return match ($category) {
            'users' => ['pending', 'active', 'suspended', 'deactivated'],
            'vehicles' => ['pending', 'approved', 'rejected', 'suspended', 'revoked', 'expired', 'not_submitted'],
            'visitors' => ['pending', 'approved', 'rejected', 'cancelled', 'expired', 'used', 'revoked'],
            'rfid' => match ($dataset) {
                'assignments' => ['active', 'expired', 'revoked', 'replaced'],
                'devices' => ['online', 'offline', 'maintenance'],
                default => ['granted', 'denied'],
            },
            'security' => ['denied', 'manual_override', 'open', 'under_review', 'resolved', 'dismissed', 'escalated', 'integrity_warning'],
            'audit' => ['recorded'],
            default => ['active', 'completed', 'force_closed', 'cancelled'],
        };
    }

    private static function normalized(Builder $query, array $expressions): Builder
    {
        return $query->selectRaw(implode(', ', array_map(fn ($key) => ($expressions[$key] ?? 'NULL')." as `$key`", self::FIELDS)));
    }

    private static function vehicleType(): string
    {
        return "CASE WHEN v.vehicle_id IS NULL THEN NULL WHEN vt.type_code = 'motorcycle' THEN 'motorcycle' ELSE 'car' END";
    }

    private static function person(): string
    {
        return "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', up.first_name, up.last_name)), ''), NULLIF(TRIM(CONCAT_WS(' ', vis.first_name, vis.last_name)), ''), u.user_code)";
    }

    private static function role(): string
    {
        return "CASE WHEN vis.visitor_id IS NOT NULL THEN 'guest' ELSE u.user_type END";
    }

    private static function joins(Builder $q, string $vehicle, string $user, string $visitor): Builder
    {
        return $q->leftJoin('vehicles as v', 'v.vehicle_id', '=', $vehicle)
            ->leftJoin('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->leftJoin('users as u', 'u.user_id', '=', $user)
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 'u.user_id')
            ->leftJoin('visitors as vis', 'vis.visitor_id', '=', $visitor);
    }

    private static function identity(): array
    {
        return ['vehicle_id' => 'v.vehicle_id', 'plate_number' => 'v.plate_number', 'vehicle_type' => self::vehicleType(), 'person' => self::person(), 'user_code' => 'u.user_code', 'user_type' => self::role()];
    }

    public static function stays(): Builder
    {
        $q = self::joins(DB::table('parking_transactions as t'), 't.vehicle_id', 't.user_id', 't.visitor_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 't.parking_area_id')
            ->leftJoin('drivers as d', 'd.driver_id', '=', 't.driver_id')
            ->leftJoin('access_events as ei', 'ei.event_id', '=', 't.entry_event_id')
            ->leftJoin('access_events as eo', 'eo.event_id', '=', 't.exit_event_id')
            ->leftJoin('access_gates as gi', 'gi.gate_id', '=', 'ei.gate_id')
            ->leftJoin('access_gates as go', 'go.gate_id', '=', 'eo.gate_id')
            ->leftJoin('rfid_tags as rt', 'rt.rfid_id', '=', 'ei.rfid_id');

        return self::normalized($q, array_merge(self::identity(), [
            'record_id' => 't.transaction_id', 'occurred_at' => 't.entry_datetime', 'status' => 't.transaction_status',
            'method' => 't.access_method', 'credential' => 'rt.tag_uid', 'entry_at' => 't.entry_datetime', 'exit_at' => 't.exit_datetime',
            'duration_minutes' => 'CASE WHEN t.exit_datetime >= t.entry_datetime THEN TIMESTAMPDIFF(SECOND, t.entry_datetime, t.exit_datetime)/60.0 ELSE NULL END',
            'area' => 'a.area_name', 'parking_area_id' => 't.parking_area_id', 'slot_id' => 't.slot_id',
            'details' => "CONCAT_WS(' | ', CONCAT('Ticket: ', t.ticket_number), CONCAT('Recorded driver: ', NULLIF(TRIM(CONCAT_WS(' ', d.first_name, d.last_name)), '')), CONCAT('Entry gate: ', gi.gate_name), CONCAT('Exit gate: ', go.gate_name), t.remarks)",
        ]));
    }

    private static function events(): Builder
    {
        $q = self::joins(DB::table('access_events as e'), 'e.vehicle_id', 'e.user_id', 'e.visitor_id')
            ->leftJoin('access_gates as g', 'g.gate_id', '=', 'e.gate_id')
            ->leftJoin('rfid_readers as rd', 'rd.reader_id', '=', 'e.reader_id')
            ->leftJoin('rfid_tags as rt', 'rt.rfid_id', '=', 'e.rfid_id')
            ->leftJoin('qr_passes as qp', 'qp.qr_pass_id', '=', 'e.qr_pass_id');

        return self::normalized($q, array_merge(self::identity(), [
            'record_id' => "CONCAT('EV-', e.event_id)", 'occurred_at' => 'e.event_datetime', 'status' => 'e.auth_result', 'method' => 'e.access_method',
            'direction' => 'e.direction', 'credential' => "CASE WHEN e.access_method = 'rfid' THEN COALESCE(rt.tag_uid, JSON_UNQUOTE(JSON_EXTRACT(e.raw_payload, '$.credential'))) ELSE CONCAT('QR#', qp.qr_pass_id) END",
            'area' => 'g.gate_name', 'parking_area_id' => 'g.parking_area_id',
            'details' => "CONCAT_WS(' | ', e.denial_reason, CONCAT('Reader: ', rd.reader_code), CONCAT('Recorded by user #', e.processed_by_user_id))",
        ]));
    }

    private static function users(): Builder
    {
        $q = DB::table('users as u')->leftJoin('user_profiles as up', 'up.user_id', '=', 'u.user_id')->whereNull('u.deleted_at');

        return self::normalized($q, [
            'record_id' => 'u.user_id', 'occurred_at' => 'u.created_at', 'person' => "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', up.first_name, up.last_name)), ''), u.user_code)",
            'user_code' => 'u.user_code', 'user_type' => "CASE WHEN EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.role_id=ur.role_id WHERE ur.user_id=u.user_id AND r.role_code='guest') THEN 'guest' ELSE u.user_type END",
            'status' => 'u.account_status', 'details' => "CONCAT_WS(' | ', CONCAT('Email: ', u.email), IF(u.email_verified_at IS NULL, 'Email unverified', 'Email verified'), IF(up.school_id_document_path IS NULL, 'School ID not submitted', 'School ID submitted'))",
        ]);
    }

    private static function vehicles(): Builder
    {
        $q = self::joins(DB::table('vehicles as base'), 'base.vehicle_id', 'base.owner_user_id', 'base.owner_visitor_id')
            ->leftJoin('vehicle_authorizations as va', 'va.vehicle_id', '=', 'v.vehicle_id')
            ->leftJoin('vehicle_driver as vd', fn ($j) => $j->on('vd.vehicle_id', '=', 'v.vehicle_id')->whereRaw('vd.driver_id = (SELECT x.driver_id FROM vehicle_driver x WHERE x.vehicle_id=v.vehicle_id ORDER BY x.is_primary DESC, x.driver_id DESC LIMIT 1)'))
            ->leftJoin('drivers as d', 'd.driver_id', '=', 'vd.driver_id')
            ->leftJoin('driver_licenses as dl', fn ($j) => $j->on('dl.driver_id', '=', 'd.driver_id')->whereRaw('dl.license_id = (SELECT MAX(x.license_id) FROM driver_licenses x WHERE x.driver_id=d.driver_id)'));
        $today = DB::getPdo()->quote(CampusTime::date());

        return self::normalized($q, array_merge(self::identity(), [
            'record_id' => "CONCAT('V-', v.vehicle_id, '-AUTH-', COALESCE(va.authorization_id, 0))", 'occurred_at' => 'COALESCE(va.requested_at, v.created_at)',
            'status' => "COALESCE(va.authorization_status, 'not_submitted')",
            'details' => "CONCAT_WS(' | ', CONCAT('Vehicle: ', v.registration_status), CONCAT('Driver: ', NULLIF(TRIM(CONCAT_WS(' ', d.first_name, d.last_name)), '')), CONCAT('License review: ', COALESCE(dl.verification_status, 'not_submitted')), CONCAT('License expiry: ', dl.expiry_date), IF(dl.expiry_date < $today, 'LICENSE EXPIRED', NULL), CONCAT('Authorization until: ', va.valid_until), CONCAT('Reviewed by #', va.reviewed_by), CONCAT('Reviewed at UTC: ', va.reviewed_at), IF(v.deleted_at IS NOT NULL, 'Vehicle deleted/archived', NULL))",
        ]));
    }

    private static function visitors(): Builder
    {
        $q = self::joins(DB::table('visitor_requests as vr'), 'vr.vehicle_id', 'vr.host_user_id', 'vr.visitor_id')
            ->leftJoin('qr_passes as qp', fn ($j) => $j->on('qp.request_id', '=', 'vr.request_id')->whereRaw('qp.qr_pass_id = (SELECT MAX(x.qr_pass_id) FROM qr_passes x WHERE x.request_id=vr.request_id)'));
        $now = DB::getPdo()->quote(now()->toDateTimeString());

        return self::normalized($q, array_merge(self::identity(), [
            'record_id' => 'vr.request_id', 'occurred_at' => 'vr.requested_valid_from', 'person' => "TRIM(CONCAT_WS(' ', vis.first_name, vis.last_name))", 'user_type' => "'guest'", 'user_code' => 'NULL',
            'status' => "CASE WHEN vr.request_status IN ('rejected','cancelled') THEN vr.request_status WHEN qp.pass_status IN ('used','revoked') THEN qp.pass_status WHEN vr.requested_valid_until < $now THEN 'expired' ELSE vr.request_status END",
            'credential' => "CONCAT('QR#', qp.qr_pass_id)", 'entry_at' => '(SELECT MIN(pt.entry_datetime) FROM parking_transactions pt JOIN access_events ae ON ae.event_id=pt.entry_event_id WHERE ae.qr_pass_id=qp.qr_pass_id)',
            'exit_at' => '(SELECT MAX(pt.exit_datetime) FROM parking_transactions pt JOIN access_events ae ON ae.event_id=pt.entry_event_id WHERE ae.qr_pass_id=qp.qr_pass_id)',
            'details' => "CONCAT_WS(' | ', CONCAT('Purpose: ', vr.purpose), CONCAT('Request: ', vr.request_status), CONCAT('Pass: ', qp.pass_status), CONCAT('Reviewed by #', vr.reviewed_by), CONCAT('Reviewed at UTC: ', vr.reviewed_at), CONCAT('Visit ends UTC: ', vr.requested_valid_until), vr.review_remarks)",
        ]));
    }

    private static function assignments(): Builder
    {
        $q = DB::table('rfid_assignments as ra')->leftJoin('rfid_tags as rt', 'rt.rfid_id', '=', 'ra.rfid_id');
        $q = self::joins($q, 'ra.vehicle_id', 'ra.user_id', 'v.owner_visitor_id');

        return self::normalized($q, array_merge(self::identity(), [
            'record_id' => 'ra.assignment_id', 'occurred_at' => 'ra.assigned_at', 'status' => 'ra.assignment_status', 'credential' => 'rt.tag_uid',
            'details' => "CONCAT_WS(' | ', CONCAT('Tag status: ', rt.tag_status), CONCAT('Assigned by #', ra.assigned_by), CONCAT('Valid until: ', ra.valid_until), CONCAT('Unassigned UTC: ', ra.unassigned_at))",
        ]));
    }

    private static function devices(): Builder
    {
        return self::normalized(DB::table('rfid_readers as rd')->leftJoin('access_gates as g', 'g.gate_id', '=', 'rd.gate_id'), [
            'record_id' => 'rd.reader_id', 'occurred_at' => 'rd.created_at', 'person' => 'rd.device_name', 'credential' => 'rd.reader_code', 'status' => 'rd.reader_status',
            'area' => 'g.gate_name', 'parking_area_id' => 'g.parking_area_id',
            'details' => "CONCAT_WS(' | ', CONCAT('Gate status: ', g.gate_status), CONCAT('Last heartbeat UTC: ', rd.last_heartbeat_at), CONCAT('Direction: ', rd.reader_direction))",
        ]);
    }

    private static function audit(): Builder
    {
        return self::normalized(DB::table('audit_logs as al')->leftJoin('users as u', 'u.user_id', '=', 'al.user_id'), [
            'record_id' => 'al.log_id', 'occurred_at' => 'al.created_at', 'person' => 'COALESCE(al.actor_label, u.user_code)', 'user_code' => 'u.user_code', 'user_type' => 'u.user_type', 'status' => "'recorded'",
            'method' => 'al.action', 'details' => "CONCAT_WS(' | ', al.action, al.auditable_type, CONCAT('Record #', al.auditable_id), CONCAT('IP: ', al.ip_address))", 'old_values' => 'al.old_values', 'new_values' => 'al.new_values',
        ]);
    }

    private static function security(): Builder
    {
        $events = self::events()->where(fn ($q) => $q->where('e.auth_result', 'denied')->orWhere('e.access_method', 'manual'));
        // Use the same normalized shape for exceptions, actionable incidents and integrity warnings.
        $events = DB::query()->fromSub($events, 'ex')->selectRaw(implode(', ', array_map(fn ($k) => $k === 'status' ? "CASE WHEN status='granted' THEN 'manual_override' ELSE status END as status" : "`$k`", self::FIELDS)));
        $q = self::joins(DB::table('parking_violations as pv'), 'pv.vehicle_id', 'pv.user_id', 'pv.visitor_id')
            ->leftJoin('violation_types as vt_inc', 'vt_inc.violation_type_id', '=', 'pv.violation_type_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 'pv.parking_area_id');
        $incidents = self::normalized($q, array_merge(self::identity(), [
            'record_id' => "CONCAT('INC-', pv.violation_id)", 'occurred_at' => 'pv.occurred_at', 'status' => 'pv.violation_status', 'area' => 'a.area_name', 'parking_area_id' => 'pv.parking_area_id',
            'details' => "CONCAT_WS(' | ', vt_inc.violation_name, pv.description, CONCAT('Resolved UTC: ', pv.resolved_at), pv.resolution_remarks)",
        ]));
        $integrity = self::stays()->where(function ($q) {
            $q->where(fn ($w) => $w->where('t.transaction_status', 'active')->where(fn ($a) => $a->whereNull('t.slot_id')->orWhereNotNull('t.exit_datetime')->orWhereExists(fn ($dup) => $dup->selectRaw('1')->from('parking_transactions as other')->where('other.transaction_status', 'active')->whereColumn('other.transaction_id', '<>', 't.transaction_id')->where(fn ($v) => $v->whereColumn('other.vehicle_id', 't.vehicle_id')->orWhereColumn('other.slot_id', 't.slot_id')->orWhere(fn ($owner) => $owner->whereNotNull('t.user_id')->whereColumn('other.user_id', 't.user_id'))))))
                ->orWhere(fn ($w) => $w->whereIn('t.transaction_status', ['completed', 'force_closed'])->whereNull('t.exit_datetime'))
                ->orWhereColumn('t.exit_datetime', '<', 't.entry_datetime');
        });
        $integrity = DB::query()->fromSub($integrity, 'iw')->selectRaw(implode(', ', array_map(fn ($k) => match ($k) {
            'record_id' => "CONCAT('CHECK-', record_id) as record_id", 'status' => "'integrity_warning' as status", 'details' => "CONCAT('Check transaction: missing/invalid exit, missing slot, duplicate active vehicle or slot. ', COALESCE(details,'')) as details", default => "`$k`",
        }, self::FIELDS)));

        return $events->unionAll($incidents)->unionAll($integrity);
    }

    public static function query(ReportFilters $f): Builder
    {
        $source = match ($f->category()) {
            'rfid' => match ($f->values['dataset']) {
                'assignments' => self::assignments(), 'devices' => self::devices(), default => self::events()->where('e.access_method', 'rfid')
            },
            'users' => self::users(), 'vehicles' => self::vehicles(), 'visitors' => self::visitors(), 'security' => self::security(), 'audit' => self::audit(),
            default => self::stays(),
        };
        $q = DB::query()->fromSub($source, 'r');
        if (in_array($f->category(), ['overview', 'entry-exit'], true)) {
            $q->where(fn ($w) => $w->where(fn ($d) => $d->where('r.entry_at', '>=', $f->start())->where('r.entry_at', '<', $f->end()))->orWhere(fn ($d) => $d->where('r.exit_at', '>=', $f->start())->where('r.exit_at', '<', $f->end())));
        } elseif ($f->category() === 'occupancy') {
            $q->whereIn('r.status', ['active', 'completed', 'force_closed'])->where('r.entry_at', '<', $f->end())->where(fn ($w) => $w->whereNull('r.exit_at')->orWhere('r.exit_at', '>', $f->start()))
                ->where(fn ($w) => $w->where(fn ($a) => $a->where('r.status', 'active')->whereNull('r.exit_at'))->orWhere(fn ($a) => $a->whereNotNull('r.exit_at')->whereColumn('r.exit_at', '>=', 'r.entry_at')));
        } elseif (! ($f->category() === 'rfid' && $f->values['dataset'] === 'devices')) {
            $q->where('r.occurred_at', '>=', $f->start())->where('r.occurred_at', '<', $f->end());
        }
        foreach (['vehicle_type', 'user_type', 'status', 'method'] as $key) {
            if (isset($f->values[$key])) {
                $q->where("r.$key", $f->values[$key]);
            }
        }
        if (isset($f->values['area_id'])) {
            $q->where('r.parking_area_id', $f->values['area_id']);
        }
        if (isset($f->values['search'])) {
            $like = '%'.trim($f->values['search']).'%';
            $q->where(function ($w) use ($like) {
                foreach (['record_id', 'plate_number', 'person', 'user_code', 'credential', 'details'] as $key) {
                    $w->orWhere("r.$key", 'like', $like);
                }
            });
        }

        return $q;
    }

    public static function ordered(Builder $q, ReportFilters $f): Builder
    {
        return match ($f->values['sort']) {
            'oldest' => $q->orderBy('r.occurred_at')->orderBy('r.record_id'),
            'duration' => $q->orderByDesc('r.duration_minutes')->orderBy('r.record_id'),
            'status' => $q->orderBy('r.status')->orderByDesc('r.occurred_at')->orderBy('r.record_id'),
            default => $q->orderByDesc('r.occurred_at')->orderByDesc('r.record_id'),
        };
    }

    public static function row(object|array $row): array
    {
        $r = (array) $row;
        foreach (['occurred_at', 'entry_at', 'exit_at'] as $key) {
            if (isset($r[$key])) {
                $r[$key] = CarbonImmutable::parse($r[$key], config('app.timezone'))->toIso8601String();
            }
        }
        if (isset($r['duration_minutes'])) {
            $r['duration_minutes'] = round((float) $r['duration_minutes'], 2);
        }
        foreach (['old_values', 'new_values'] as $key) {
            if (isset($r[$key])) {
                $r[$key] = self::redact(is_string($r[$key]) ? json_decode($r[$key], true) : $r[$key]);
            }
        }

        return $r;
    }

    public static function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return null;
        }
        $safe = [];
        foreach ($value as $key => $v) {
            if (preg_match('/password|token|secret|api.?key|document|file|image|license.?number|license.?no|pass.?code|qr.?code|raw.?payload/i', (string) $key)) {
                continue;
            }
            $safe[$key] = is_array($v) ? self::redact($v) : $v;
        }

        return $safe;
    }
}
