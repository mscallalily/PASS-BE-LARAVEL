<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_assignments', function (Blueprint $table) {
            $table->bigIncrements('assignment_id');
            $table->unsignedBigInteger('rfid_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();   // optional pairing
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->dateTime('assigned_at')->useCurrent();
            $table->date('valid_until')->nullable();
            $table->dateTime('unassigned_at')->nullable();
            $table->enum('assignment_status', ['active', 'expired', 'revoked', 'replaced'])->default('active');
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->foreign('rfid_id')->references('rfid_id')->on('rfid_tags');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles');
            $table->foreign('assigned_by')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_assignments');
    }
};