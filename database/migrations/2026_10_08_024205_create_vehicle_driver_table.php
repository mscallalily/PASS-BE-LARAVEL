<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_driver', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('driver_id');
            $table->enum('relationship', ['owner', 'authorized_driver', 'family', 'company'])->default('owner');
            $table->boolean('is_primary')->default(false);
            $table->date('authorized_from')->nullable();
            $table->date('authorized_until')->nullable();
            $table->timestamps();

            $table->primary(['vehicle_id', 'driver_id']);
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('driver_id')->references('driver_id')->on('drivers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_driver');
    }
};