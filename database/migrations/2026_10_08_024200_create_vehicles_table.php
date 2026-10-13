<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->bigIncrements('vehicle_id');
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->unsignedBigInteger('owner_visitor_id')->nullable();
            $table->unsignedTinyInteger('vehicle_type_id');
            $table->string('plate_number', 20)->unique();
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->unsignedSmallInteger('year_model')->nullable();
            $table->string('or_number', 50)->nullable();
            $table->string('cr_number', 50)->nullable();
            $table->string('chassis_number', 50)->nullable();
            $table->enum('registration_status', ['registered', 'archived'])->default('registered');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('owner_user_id')->references('user_id')->on('users');
            $table->foreign('owner_visitor_id')->references('visitor_id')->on('visitors');
            $table->foreign('vehicle_type_id')->references('vehicle_type_id')->on('vehicle_types');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};