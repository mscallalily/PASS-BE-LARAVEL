<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_readers', function (Blueprint $table) {
            $table->increments('reader_id');
            $table->string('reader_code', 30)->unique();
            $table->unsignedInteger('gate_id');
            $table->string('device_name', 100)->nullable();
            $table->string('hardware_serial', 100)->nullable();
            $table->string('firmware_version', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 50)->nullable();
            $table->string('api_key_hash')->nullable();        // authenticates the reader to the API
            $table->enum('reader_direction', ['in', 'out', 'both'])->default('both');
            $table->enum('reader_status', ['online', 'offline', 'maintenance'])->default('offline');
            $table->dateTime('last_heartbeat_at')->nullable();
            $table->timestamps();

            $table->foreign('gate_id')->references('gate_id')->on('access_gates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_readers');
    }
};