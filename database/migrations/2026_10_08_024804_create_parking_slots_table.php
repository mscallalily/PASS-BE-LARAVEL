<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_slots', function (Blueprint $table) {
            $table->bigIncrements('slot_id');
            $table->unsignedInteger('zone_id');
            $table->string('slot_code', 30);
            $table->unsignedTinyInteger('vehicle_type_id')->nullable();
            $table->enum('slot_status', ['available', 'reserved', 'maintenance', 'disabled'])->default('available');
            $table->boolean('is_pwd_slot')->default(false);
            $table->unsignedBigInteger('reserved_for_user_id')->nullable();
            $table->smallInteger('pos_x')->nullable();
            $table->smallInteger('pos_y')->nullable();
            $table->unsignedSmallInteger('slot_width')->nullable();
            $table->unsignedSmallInteger('slot_height')->nullable();
            $table->smallInteger('rotation_deg')->default(0);
            $table->timestamps();

            $table->unique(['zone_id', 'slot_code']);                      // slot code unique within zone
            $table->foreign('zone_id')->references('zone_id')->on('parking_zones');
            $table->foreign('vehicle_type_id')->references('vehicle_type_id')->on('vehicle_types');
            $table->foreign('reserved_for_user_id')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_slots');
    }
};