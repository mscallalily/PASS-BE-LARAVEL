<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedBigInteger('user_id')->nullable();        // null for system jobs
            $table->string('actor_label')->nullable();                // snapshot, survives user deletion
            $table->string('action', 50);                             // created, updated, approved, revoked
            $table->string('auditable_type', 150)->nullable();        // model class
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};