<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_licenses', function (Blueprint $table) {
            $table->bigIncrements('license_id');
            $table->unsignedBigInteger('driver_id');
            $table->string('license_number', 50)->unique();
            $table->enum('license_type', ['student_permit', 'non_professional', 'professional', 'international']);
            $table->string('restriction_codes', 50)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_agency', 100)->nullable();
            $table->string('license_image_path')->nullable();
            $table->enum('license_status', ['valid', 'expired', 'suspended', 'revoked'])->default('valid');
            $table->timestamps();

            $table->foreign('driver_id')->references('driver_id')->on('drivers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_licenses');
    }
};