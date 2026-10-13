<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_gates', function (Blueprint $table) {
            $table->increments('gate_id');
            $table->unsignedInteger('parking_area_id');
            $table->string('gate_code', 30)->unique();
            $table->string('gate_name', 100);
            $table->enum('gate_type', ['entry', 'exit', 'both'])->default('both');
            $table->enum('gate_status', ['online', 'offline', 'maintenance'])->default('online');
            $table->timestamps();

            $table->foreign('parking_area_id')->references('parking_area_id')->on('parking_areas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_gates');
    }
};