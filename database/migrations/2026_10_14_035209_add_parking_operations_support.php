<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which parking area each vehicle type is allowed to use (e.g. all car
        // types -> Car Parking Area, motorcycle -> Motorcycle Parking Area).
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->unsignedInteger('parking_area_id')->nullable()->after('requires_assigned_slot');
            $table->foreign('parking_area_id')->references('parking_area_id')->on('parking_areas')->nullOnDelete();
        });

        // PASS does not support reservations.
        DB::table('parking_slots')->where('slot_status', 'reserved')->update(['slot_status' => 'available']);
        DB::statement("ALTER TABLE parking_slots MODIFY slot_status ENUM('available', 'maintenance', 'disabled') NOT NULL DEFAULT 'available'");

        // A slot assignment is not proof that the vehicle is physically in it.
        // Staff confirm the actual location; there is no slot-level sensor.
        Schema::table('parking_transactions', function (Blueprint $table) {
            $table->dateTime('slot_confirmed_at')->nullable()->after('slot_id');
            $table->unsignedBigInteger('slot_confirmed_by')->nullable()->after('slot_confirmed_at');
            $table->foreign('slot_confirmed_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Wrong-slot incidents record the slot the vehicle was assigned to;
        // slot_id holds the slot where it was actually observed.
        Schema::table('parking_violations', function (Blueprint $table) {
            $table->unsignedBigInteger('expected_slot_id')->nullable()->after('slot_id');
            $table->foreign('expected_slot_id')->references('slot_id')->on('parking_slots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parking_violations', function (Blueprint $table) {
            $table->dropForeign(['expected_slot_id']);
            $table->dropColumn('expected_slot_id');
        });

        Schema::table('parking_transactions', function (Blueprint $table) {
            $table->dropForeign(['slot_confirmed_by']);
            $table->dropColumn(['slot_confirmed_at', 'slot_confirmed_by']);
        });

        DB::statement("ALTER TABLE parking_slots MODIFY slot_status ENUM('available', 'reserved', 'maintenance', 'disabled') NOT NULL DEFAULT 'available'");

        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropForeign(['parking_area_id']);
            $table->dropColumn('parking_area_id');
        });
    }
};
