<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OR/CR documents for campus vehicle registration and the automatic document
 * check result on each authorization. Additive and nullable: existing vehicles
 * and authorizations are unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'or_document_path')) $table->string('or_document_path')->nullable()->after('vehicle_photo_path');
            if (!Schema::hasColumn('vehicles', 'cr_document_path')) $table->string('cr_document_path')->nullable()->after('or_document_path');
        });
        Schema::table('vehicle_authorizations', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_authorizations', 'document_check_status')) $table->string('document_check_status', 30)->nullable()->after('authorization_status');
            if (!Schema::hasColumn('vehicle_authorizations', 'document_check_run_id')) $table->char('document_check_run_id', 36)->nullable()->after('document_check_status');
            if (!Schema::hasColumn('vehicle_authorizations', 'document_check_result')) $table->json('document_check_result')->nullable()->after('document_check_run_id');
            if (!Schema::hasColumn('vehicle_authorizations', 'document_checked_at')) $table->timestamp('document_checked_at')->nullable()->after('document_check_result');
            if (!Schema::hasColumn('vehicle_authorizations', 'approval_source')) $table->string('approval_source', 20)->nullable()->after('document_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_authorizations', function (Blueprint $table) {
            foreach (['approval_source', 'document_checked_at', 'document_check_result', 'document_check_run_id', 'document_check_status'] as $column) {
                if (Schema::hasColumn('vehicle_authorizations', $column)) $table->dropColumn($column);
            }
        });
        Schema::table('vehicles', function (Blueprint $table) {
            foreach (['cr_document_path', 'or_document_path'] as $column) {
                if (Schema::hasColumn('vehicles', $column)) $table->dropColumn($column);
            }
        });
    }
};
