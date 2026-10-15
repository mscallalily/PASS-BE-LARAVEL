
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_sensor_readings', function (Blueprint $table) {
            $table->id('reading_id');

            $table->foreignId('sensor_id')
                ->constrained('parking_sensors', 'sensor_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('occupancy_status', [
                'occupied',
                'vacant',
                'unknown',
            ])->default('unknown');

            $table->timestamp('detected_at');
            $table->timestamp('received_at')->useCurrent();

            $table->string('source', 30)->default('sensor');
            $table->string('event_reference', 100)->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index(['sensor_id', 'detected_at']);
            $table->index(['occupancy_status', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_sensor_readings');
    }
};
