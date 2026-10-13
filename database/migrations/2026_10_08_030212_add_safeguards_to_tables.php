<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. One approved authorization per vehicle
        DB::statement("ALTER TABLE vehicle_authorizations
            ADD COLUMN active_flag TINYINT GENERATED ALWAYS AS (IF(authorization_status = 'approved', 1, NULL)) STORED,
            ADD UNIQUE INDEX uq_vehicle_one_active_auth (vehicle_id, active_flag)");

        // 2. One valid license per driver
        DB::statement("ALTER TABLE driver_licenses
            ADD COLUMN current_flag TINYINT GENERATED ALWAYS AS (IF(license_status = 'valid', 1, NULL)) STORED,
            ADD UNIQUE INDEX uq_driver_one_current_license (driver_id, current_flag)");

        // 3. One active assignment per RFID tag
        DB::statement("ALTER TABLE rfid_assignments
            ADD COLUMN active_flag TINYINT GENERATED ALWAYS AS (IF(assignment_status = 'active', 1, NULL)) STORED,
            ADD UNIQUE INDEX uq_tag_one_active_assignment (rfid_id, active_flag)");

        // 4, 5, 6. Parking transactions: no double entry, no double booking, duration
        DB::statement("ALTER TABLE parking_transactions
            ADD COLUMN active_vehicle_flag TINYINT GENERATED ALWAYS AS (IF(transaction_status = 'active', 1, NULL)) STORED,
            ADD COLUMN active_slot_flag TINYINT GENERATED ALWAYS AS (IF(transaction_status = 'active' AND slot_id IS NOT NULL, 1, NULL)) STORED,
            ADD COLUMN duration_minutes INT GENERATED ALWAYS AS (IF(exit_datetime IS NULL, NULL, TIMESTAMPDIFF(MINUTE, entry_datetime, exit_datetime))) STORED,
            ADD UNIQUE INDEX uq_vehicle_one_active_stay (vehicle_id, active_vehicle_flag),
            ADD UNIQUE INDEX uq_slot_one_active_stay (slot_id, active_slot_flag)");

        // 7. Exactly one of user_id or visitor_id
        DB::statement("ALTER TABLE parking_transactions
            ADD CONSTRAINT chk_txn_one_party CHECK ((user_id IS NULL) <> (visitor_id IS NULL))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE parking_transactions DROP CHECK chk_txn_one_party");
        DB::statement("ALTER TABLE parking_transactions DROP INDEX uq_slot_one_active_stay, DROP INDEX uq_vehicle_one_active_stay, DROP COLUMN duration_minutes, DROP COLUMN active_slot_flag, DROP COLUMN active_vehicle_flag");
        DB::statement("ALTER TABLE rfid_assignments DROP INDEX uq_tag_one_active_assignment, DROP COLUMN active_flag");
        DB::statement("ALTER TABLE driver_licenses DROP INDEX uq_driver_one_current_license, DROP COLUMN current_flag");
        DB::statement("ALTER TABLE vehicle_authorizations DROP INDEX uq_vehicle_one_active_auth, DROP COLUMN active_flag");
    }
};