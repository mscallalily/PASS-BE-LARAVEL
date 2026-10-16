<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_slot_simulations', function (Blueprint $table) {
            $table->id();

            $table->string('vehicle_type', 20);
            $table->unsignedInteger('slot_number');

            $table->boolean('is_occupied')->default(false);

            $table->foreignId('updated_by')->nullable();

            $table->timestamps();

            $table->unique(['vehicle_type', 'slot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_slot_simulations');
    }
};
