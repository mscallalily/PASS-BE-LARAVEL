<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_requests', function (Blueprint $table) {
            $table->bigIncrements('request_id');
            $table->unsignedBigInteger('visitor_id');
            $table->unsignedBigInteger('host_user_id')->nullable();        // staff member being visited
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('purpose')->nullable();
            $table->unsignedInteger('destination_area_id')->nullable();
            $table->dateTime('requested_valid_from');
            $table->dateTime('requested_valid_until');
            $table->enum('request_status', ['pending', 'approved', 'rejected', 'cancelled', 'expired'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_remarks')->nullable();
            $table->timestamps();

            $table->foreign('visitor_id')->references('visitor_id')->on('visitors');
            $table->foreign('host_user_id')->references('user_id')->on('users');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles');
            $table->foreign('destination_area_id')->references('parking_area_id')->on('parking_areas');
            $table->foreign('reviewed_by')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_requests');
    }
};