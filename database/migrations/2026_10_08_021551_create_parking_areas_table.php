<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_areas', function (Blueprint $table) {
            $table->increments('parking_area_id');
            $table->string('area_code', 30)->unique();
            $table->string('area_name', 150);
            $table->string('building', 150)->nullable();
            $table->string('floor_level', 50)->nullable();
            $table->string('description', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_areas');
    }
};