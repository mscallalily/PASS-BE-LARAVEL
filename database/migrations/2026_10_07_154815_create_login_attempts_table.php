<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
    $table->bigIncrements('attempt_id');
    $table->unsignedBigInteger('user_id')->nullable();   // null if unknown account
    $table->string('identifier');                         // email or ID typed
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent')->nullable();
    $table->boolean('was_successful')->default(false);
    $table->string('failure_reason')->nullable();
    $table->dateTime('attempted_at')->useCurrent();

    $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
