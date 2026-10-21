<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add "suspended" so staff can temporarily block an approved
     * authorization and later restore it without creating a new record.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vehicle_authorizations
            MODIFY authorization_status ENUM('pending', 'approved', 'rejected', 'revoked', 'expired', 'suspended')
            NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Suspended rows cannot exist without the enum value, so treat them as revoked.
        DB::table('vehicle_authorizations')
            ->where('authorization_status', 'suspended')
            ->update(['authorization_status' => 'revoked']);

        DB::statement("ALTER TABLE vehicle_authorizations
            MODIFY authorization_status ENUM('pending', 'approved', 'rejected', 'revoked', 'expired')
            NOT NULL DEFAULT 'pending'");
    }
};
