<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_transactions', function (Blueprint $table) {
            $table->bigIncrements('transaction_id');
            $table->string('ticket_number', 50)->unique();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();               // null for visitor stays
            $table->unsignedBigInteger('visitor_id')->nullable();            // null for registered stays
            $table->unsignedInteger('parking_area_id');
            $table->unsignedBigInteger('slot_id')->nullable();               // null in open zones
            $table->enum('access_method', ['rfid', 'qr', 'manual']);
            $table->unsignedBigInteger('entry_event_id')->nullable()->unique();
            $table->unsignedBigInteger('exit_event_id')->nullable()->unique();
            $table->dateTime('entry_datetime');
            $table->dateTime('exit_datetime')->nullable();
            $table->enum('transaction_status', ['active', 'completed', 'force_closed', 'cancelled'])->default('active');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index('entry_datetime');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles');
            $table->foreign('driver_id')->references('driver_id')->on('drivers');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('visitor_id')->references('visitor_id')->on('visitors');
            $table->foreign('parking_area_id')->references('parking_area_id')->on('parking_areas');
            $table->foreign('slot_id')->references('slot_id')->on('parking_slots');
            $table->foreign('entry_event_id')->references('event_id')->on('access_events');
            $table->foreign('exit_event_id')->references('event_id')->on('access_events');
            $table->foreign('recorded_by')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_transactions');
    }
};