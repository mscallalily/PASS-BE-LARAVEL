<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers the account status at the moment of archiving so a restore can
 * return to it instead of clearing a suspension, rejection or pending state.
 * Additive and nullable: existing archived rows (NULL) keep the previous
 * restore behaviour.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'pre_archive_status')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('pre_archive_status', 20)->nullable()->after('account_status'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'pre_archive_status')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('pre_archive_status'));
        }
    }
};
