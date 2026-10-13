<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->increments('setting_id');
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->enum('data_type', ['string', 'integer', 'decimal', 'boolean', 'json', 'time', 'date'])->default('string');
            $table->string('setting_group', 50)->nullable();          // parking, security, notification
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(false);             // readable by the mobile app
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};