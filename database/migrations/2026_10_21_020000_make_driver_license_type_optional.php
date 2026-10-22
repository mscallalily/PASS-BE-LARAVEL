<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the legacy column and existing values for retained history.
        Schema::table('driver_licenses', function (Blueprint $table) {
            $table->enum('license_type', ['student_permit', 'non_professional', 'professional', 'international'])->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('driver_licenses')->whereNull('license_type')->exists()) {
            throw new RuntimeException('Cannot restore a required license type while unclassified licenses exist. No classifications were invented or records deleted.');
        }
        Schema::table('driver_licenses', function (Blueprint $table) {
            $table->enum('license_type', ['student_permit', 'non_professional', 'professional', 'international'])->nullable(false)->change();
        });
    }
};
