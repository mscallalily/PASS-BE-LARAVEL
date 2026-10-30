<?php

namespace App\Support;

use App\Services\Reports\ReportDataset;

/** Presentation metadata only. Never changes an audit record or a review decision. */
final class AuditActivity
{
    public const OUTCOMES = ['completed' => 'Completed', 'recorded' => 'Recorded', 'denied' => 'Access denied', 'attention' => 'Needs attention', 'failed' => 'Failed'];
    public const MODULES = ['accounts' => 'Accounts & access', 'vehicles' => 'Vehicles & drivers', 'rfid' => 'RFID access', 'parking' => 'Parking operations', 'visitors' => 'Visitors & QR', 'violations' => 'Violations', 'settings' => 'System settings', 'system' => 'System activity'];

    public static function normalize(string $value): string
    {
        $value = preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '_', $value);
        return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $value)), '_');
    }

    public static function outcome(string $action): string
    {
        $value = self::normalize($action);
        if (preg_match('/(?:^|_)(failed|failure|error)(?:_|$)/', $value)) return 'failed';
        if (preg_match('/(?:^|_)denied(?:_|$)/', $value)) return 'denied';
        if (preg_match('/(?:^|_)(conflict|mismatch|offline|disconnected|incomplete)(?:_|$)|force_closed/', $value)) return 'attention';
        if (preg_match('/(?:^|_)(requested|submitted|resubmitted|pending)(?:_|$)/', $value)) return 'recorded';
        if (in_array($value, ['login', 'logout', 'signed_in', 'signed_out'], true) || preg_match('/(?:^|_)(created|updated|approved|rejected|activated|deactivated|suspended|lifted|assigned|revoked|archived|restored|deleted|changed|reset|completed|granted|recorded|reviewed|resolved|dismissed|cancelled|canceled|selected|released|release|available|verified)(?:_|$)/', $value)) return 'completed';
        return 'recorded';
    }

    public static function module(?string $type): string
    {
        $type = self::normalize(basename(str_replace('\\', '/', $type ?? '')));
        if (preg_match('/rfid|reader/', $type)) return 'rfid';
        if (preg_match('/visitor|qr_pass|vehicle_release/', $type)) return 'visitors';
        if (preg_match('/violation|evidence/', $type)) return 'violations';
        if (preg_match('/vehicle|driver|license|authoriz/', $type)) return 'vehicles';
        if (preg_match('/parking|access_event|access_gate|gate_access|slot/', $type)) return 'parking';
        if (preg_match('/user|account|profile|role|permission|login/', $type)) return 'accounts';
        if (preg_match('/setting|department/', $type)) return 'settings';
        return 'system';
    }

    public static function label(string $action): string
    {
        $value = self::normalize($action);
        $special = ['login' => 'Signed in', 'logout' => 'Signed out', 'rfid_assigned' => 'RFID assigned', 'rfid_revoked' => 'RFID access revoked', 'vehicle_selected_for_use' => 'Vehicle selected', 'profile_contact_updated' => 'Contact details updated', 'priority_alert_reviewed' => 'Priority alert reviewed'];
        if (isset($special[$value])) return $special[$value];
        return preg_replace_callback('/\b(rfid|qr|oled|id|api)\b/i', fn ($m) => strtoupper($m[0]), ucfirst(str_replace('_', ' ', $value))) ?: 'Activity recorded';
    }

    public static function recordLabel(?string $type, string|int|null $id, ?array $values = null): string
    {
        $values ??= [];
        foreach (['plate_number', 'user_code', 'slot_code', 'reader_code', 'area_name', 'zone_name', 'gate_name'] as $key) {
            if (isset($values[$key]) && is_scalar($values[$key]) && trim((string) $values[$key]) !== '') return mb_substr((string) $values[$key], 0, 120);
        }
        $name = trim(implode(' ', array_filter([$values['first_name'] ?? null, $values['last_name'] ?? null], 'is_string')));
        if ($name !== '') return mb_substr($name, 0, 120);
        if (isset($values['email']) && is_string($values['email'])) return mb_substr($values['email'], 0, 120);
        $key = self::normalize(basename(str_replace('\\', '/', $type ?? 'record')));
        $labels = ['users' => 'Account', 'user' => 'Account', 'user_profiles' => 'Account information', 'vehicles' => 'Vehicle', 'vehicle' => 'Vehicle', 'parking_transactions' => 'Parking stay', 'parking_transaction' => 'Parking stay', 'parking_slots' => 'Parking slot', 'rfid_assignments' => 'RFID assignment', 'access_events' => 'Gate event', 'audit_logs' => 'Activity log'];
        return ($labels[$key] ?? self::label($key)).($id !== null ? ' #'.$id : '');
    }

    public static function safeValues(mixed $values): ?array
    {
        $values = ReportDataset::redact($values);
        if ($values === null) return null;
        $safe = [];
        foreach ($values as $key => $value) {
            if (preg_match('/photo|_path$|reader.?key|private.?key/i', (string) $key)) continue;
            $safe[$key] = is_array($value) ? self::safeValues($value) : $value;
        }
        return $safe;
    }

    public static function present(string $action, ?string $type, string|int|null $id, ?array $values = null): array
    {
        $outcome = self::outcome($action); $module = self::module($type);
        $variant = ['completed' => 'success', 'recorded' => 'info', 'denied' => 'warning', 'attention' => 'warning', 'failed' => 'danger'][$outcome];
        return ['action' => self::label($action), 'module' => $module, 'module_label' => self::MODULES[$module], 'outcome' => $outcome, 'outcome_label' => self::OUTCOMES[$outcome], 'variant' => $variant, 'record_label' => self::recordLabel($type, $id, $values)];
    }
}
