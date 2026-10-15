<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DYCI has one Main Entrance/Exit shared by cars and motorcycles, so a gate
     * may serve every parking area. NULL parking_area_id = shared campus gate.
     */
    public function up(): void
    {
        Schema::table('access_gates', function (Blueprint $table) {
            $table->unsignedInteger('parking_area_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Only reversible while every gate still belongs to an area.
        Schema::table('access_gates', function (Blueprint $table) {
            $table->unsignedInteger('parking_area_id')->nullable(false)->change();
        });
    }
};
