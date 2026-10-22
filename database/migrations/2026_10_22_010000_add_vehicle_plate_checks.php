<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_authorizations', function (Blueprint $table) {
            $table->string('plate_check_status', 32)->nullable();
            $table->uuid('plate_check_run_id')->nullable();
            $table->string('plate_check_message', 255)->nullable();
            $table->dateTime('plate_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_authorizations', fn (Blueprint $table) => $table->dropColumn([
            'plate_check_status', 'plate_check_run_id', 'plate_check_message', 'plate_checked_at',
        ]));
    }
};
