<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registration_sessions', function (Blueprint $table) {
            $table->uuid('session_id')->primary();
            $table->string('email', 255)->index();
            $table->char('owner_hash', 64);
            $table->longText('draft'); // Laravel encrypted: contains personal details, never a password.
            $table->string('code_hash')->nullable();
            $table->unsignedTinyInteger('code_attempts')->default(0);
            $table->dateTime('code_sent_at')->nullable();
            $table->dateTime('code_expires_at')->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->char('capture_hash', 64)->nullable();
            $table->dateTime('capture_expires_at')->nullable();
            $table->uuid('license_run_id')->nullable();
            $table->dateTime('license_started_at')->nullable();
            $table->string('document_path')->nullable();
            $table->char('document_hash', 64)->nullable();
            $table->longText('license_result')->nullable(); // encrypted OCR evidence
            $table->dateTime('consumed_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('expires_at')->index();
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void { Schema::dropIfExists('registration_sessions'); }
};
