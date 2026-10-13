<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_authorizations', function (Blueprint $table) {
            $table->bigIncrements('authorization_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedInteger('parking_area_id')->nullable();   // null means all areas
            $table->string('sticker_number', 50)->nullable()->unique();
            $table->enum('authorization_status', ['pending', 'approved', 'rejected', 'revoked', 'expired'])->default('pending');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->dateTime('requested_at')->useCurrent();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('parking_area_id')->references('parking_area_id')->on('parking_areas')->nullOnDelete();
            $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_authorizations');
    }
};