<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_zones', function (Blueprint $table) {
            $table->increments('zone_id');
            $table->unsignedInteger('parking_area_id');
            $table->string('zone_code', 30);
            $table->string('zone_name', 100);
            $table->unsignedTinyInteger('vehicle_type_id')->nullable();   // default type for the zone
            $table->unsignedSmallInteger('declared_capacity')->default(0);
            $table->unsignedSmallInteger('layout_width')->nullable();
            $table->unsignedSmallInteger('layout_height')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parking_area_id')->references('parking_area_id')->on('parking_areas');
            $table->foreign('vehicle_type_id')->references('vehicle_type_id')->on('vehicle_types');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_zones');
    }
};