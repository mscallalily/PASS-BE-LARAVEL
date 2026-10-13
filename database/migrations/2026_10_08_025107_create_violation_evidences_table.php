<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_evidences', function (Blueprint $table) {
            $table->bigIncrements('evidence_id');
            $table->unsignedBigInteger('violation_id');
            $table->string('file_path');
            $table->enum('file_type', ['image', 'video', 'document'])->default('image');
            $table->string('caption')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->dateTime('uploaded_at')->useCurrent();

            $table->foreign('violation_id')->references('violation_id')->on('parking_violations')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_evidences');
    }
};