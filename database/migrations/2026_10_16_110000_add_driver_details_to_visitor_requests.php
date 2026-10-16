<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visitor_requests', function (Blueprint $table) {
            $table->boolean('driver_is_requester')->nullable();
            $table->string('driver_name', 200)->nullable();
            $table->text('driver_license_encrypted')->nullable();
            $table->date('driver_license_expiry')->nullable();
            $table->unsignedTinyInteger('passenger_count')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('visitor_requests', fn (Blueprint $table) => $table->dropColumn([
            'driver_is_requester', 'driver_name', 'driver_license_encrypted', 'driver_license_expiry', 'passenger_count',
        ]));
    }
};
