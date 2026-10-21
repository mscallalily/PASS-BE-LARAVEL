<?php

namespace App\Support;

final class RecordHistoryCatalog
{
    public const ENTITIES = [
        'users' => 'Accounts', 'user_profiles' => 'Account information', 'user_roles' => 'Account roles',
        'roles' => 'Roles', 'role_permissions' => 'Role permissions', 'drivers' => 'Drivers',
        'driver_licenses' => 'Driver licenses', 'vehicles' => 'Vehicles', 'vehicle_authorizations' => 'Vehicle authorizations',
        'vehicle_driver' => 'Vehicle drivers', 'user_vehicle_selections' => 'Selected vehicles',
        'rfid_tags' => 'RFID tags', 'rfid_assignments' => 'RFID assignments', 'rfid_readers' => 'RFID readers',
        'visitors' => 'Visitors', 'visitor_requests' => 'Visitor requests', 'qr_passes' => 'Visitor passes',
        'vehicle_release_requests' => 'Vehicle release requests', 'parking_transactions' => 'Parking transactions',
        'access_events' => 'Entry and exit events', 'parking_violations' => 'Violations', 'violation_evidences' => 'Violation evidence',
        'parking_areas' => 'Parking areas', 'parking_zones' => 'Parking zones', 'parking_slots' => 'Parking slots',
        'access_gates' => 'Gates', 'departments' => 'Departments', 'vehicle_types' => 'Vehicle types',
        'login_attempts' => 'Sign-in attempts', 'audit_logs' => 'Activity logs', 'audit_alert_reviews' => 'Priority alert reviews',
    ];

    public const DOCUMENTS = [
        'user_profiles' => ['school_id_document_path'], 'driver_licenses' => ['license_image_path'],
        'vehicles' => ['vehicle_photo_path'], 'visitor_requests' => ['driver_license_photo_path', 'vehicle_photo_path'],
        'violation_evidences' => ['file_path'],
    ];

    public static function retainedColumn(string $table, string $column): bool
    {
        return !preg_match('/password|token|secret|api_key|private_key|reader_key|remember|pass_code|verification_run_id|raw_payload/i', $column)
            && !($table === 'audit_logs' && in_array($column, ['old_values', 'new_values'], true));
    }
}
