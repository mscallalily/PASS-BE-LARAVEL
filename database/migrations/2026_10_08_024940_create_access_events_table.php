<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_events', function (Blueprint $table) {
            $table->bigIncrements('event_id');
            $table->dateTime('event_datetime')->useCurrent();
            $table->enum('direction', ['in', 'out']);
            $table->enum('access_method', ['rfid', 'qr', 'manual']);
            $table->unsignedInteger('gate_id');
            $table->unsignedInteger('reader_id')->nullable();
            $table->unsignedBigInteger('rfid_id')->nullable();               // set when method is rfid
            $table->unsignedBigInteger('rfid_assignment_id')->nullable();
            $table->unsignedBigInteger('qr_pass_id')->nullable();            // set when method is qr
            $table->unsignedBigInteger('user_id')->nullable();               // resolved identity
            $table->unsignedBigInteger('visitor_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->enum('auth_result', ['granted', 'denied']);
            $table->string('denial_reason')->nullable();
            $table->json('raw_payload')->nullable();                         // unparsed reader frame
            $table->unsignedBigInteger('processed_by_user_id')->nullable();  // manual override operator
            $table->timestamps();

            $table->index('event_datetime');
            $table->foreign('gate_id')->references('gate_id')->on('access_gates');
            $table->foreign('reader_id')->references('reader_id')->on('rfid_readers');
            $table->foreign('rfid_id')->references('rfid_id')->on('rfid_tags');
            $table->foreign('rfid_assignment_id')->references('assignment_id')->on('rfid_assignments');
            $table->foreign('qr_pass_id')->references('qr_pass_id')->on('qr_passes');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('visitor_id')->references('visitor_id')->on('visitors');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles');
            $table->foreign('processed_by_user_id')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_events');
    }
};