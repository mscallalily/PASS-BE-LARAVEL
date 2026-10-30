
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_sensors', function (Blueprint $table) {
            $table->id('sensor_id');

            $table->foreignId('slot_id')
                ->constrained('parking_slots', 'slot_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('sensor_code', 100)->unique();
            $table->string('sensor_type', 50)->nullable();
            $table->string('hardware_model', 100)->nullable();

            $table->enum('sensor_status', [
                'unconfigured',
                'online',
                'offline',
                'maintenance',
            ])->default('unconfigured');

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['slot_id', 'sensor_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_sensors');
    }
};
