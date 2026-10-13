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
        Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('user_id');
    $table->string('user_code', 50)->unique();      // student or employee number
    $table->string('email')->unique();
    $table->string('password');                      // bcrypt hash
    $table->enum('user_type', ['student', 'faculty', 'employee', 'security', 'admin', 'other']);
    $table->enum('account_status', ['pending', 'active', 'suspended', 'deactivated'])->default('pending');
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
