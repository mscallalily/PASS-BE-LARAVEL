<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->tinyIncrements('vehicle_type_id');
            $table->string('type_code', 30)->unique();
            $table->string('type_name', 100);
            $table->boolean('requires_assigned_slot')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};