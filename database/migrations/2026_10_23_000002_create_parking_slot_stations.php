<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_slot_stations', function (Blueprint $table) {
            $table->bigIncrements('station_id');
            $table->unsignedInteger('reader_id')->unique();
            $table->unsignedBigInteger('slot_id')->unique();
            $table->uuid('session_id')->nullable();
            $table->unsignedBigInteger('last_sequence')->default(0);
            $table->boolean('oled_ready')->default(false);
            $table->string('sensor_state', 12)->default('unknown');
            $table->decimal('distance_cm', 6, 1)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('last_free_seen_at')->nullable();
            $table->dateTime('state_changed_at')->nullable();
            $table->unsignedBigInteger('detected_transaction_id')->nullable();
            $table->unsignedBigInteger('configured_by');
            $table->timestamps();
            $table->foreign('reader_id')->references('reader_id')->on('rfid_readers');
            $table->foreign('slot_id')->references('slot_id')->on('parking_slots');
            $table->foreign('configured_by')->references('user_id')->on('users');
        });
        Schema::table('parking_transactions', function (Blueprint $table) {
            $table->dateTime('slot_sensor_detected_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parking_transactions', fn (Blueprint $table) => $table->dropColumn('slot_sensor_detected_at'));
        Schema::dropIfExists('parking_slot_stations');
    }
};
