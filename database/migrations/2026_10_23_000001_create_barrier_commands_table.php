<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('barrier_commands', function (Blueprint $table) {
            $table->bigIncrements('command_id');
            $table->unsignedBigInteger('event_id')->unique();
            $table->unsignedInteger('gate_id');
            $table->uuid('nonce')->unique();
            $table->text('receipt')->nullable();
            $table->string('command_status', 20)->default('pending');
            $table->unsignedInteger('claimed_by_reader_id')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('claimed_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
            $table->index(['gate_id', 'command_status', 'expires_at']);
            $table->foreign('event_id')->references('event_id')->on('access_events');
            $table->foreign('gate_id')->references('gate_id')->on('access_gates');
            $table->foreign('claimed_by_reader_id')->references('reader_id')->on('rfid_readers');
        });
    }
    public function down(): void { Schema::dropIfExists('barrier_commands'); }
};
