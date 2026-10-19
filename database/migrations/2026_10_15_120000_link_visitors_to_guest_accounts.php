<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Guest accounts: a visitor may have a PASS login (users row with the guest
 * role). Additive only: existing visitors keep user_id NULL (staff-registered
 * visitors without an account), and no existing row is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('visitors', 'user_id')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->unique()->after('visitor_id');
                $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
            });
        }

        if (!DB::table('roles')->where('role_code', 'guest')->exists()) {
            DB::table('roles')->insert([
                'role_code' => 'guest',
                'role_name' => 'Guest / Visitor',
                'description' => 'Visitor account: requests visitor parking and uses a Visitor QR pass',
                'is_system' => true,
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('visitors', 'user_id')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropUnique(['user_id']);
                $table->dropColumn('user_id');
            });
        }
        // The guest role row is left in place: accounts may still reference it.
    }
};
