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
        Schema::create('user_profiles', function (Blueprint $table) {
    $table->bigIncrements('profile_id');
    $table->unsignedBigInteger('user_id')->unique();
    $table->unsignedInteger('department_id')->nullable();
    $table->string('first_name', 100);
    $table->string('middle_name', 100)->nullable();
    $table->string('last_name', 100);
    $table->string('suffix', 20)->nullable();
    $table->enum('sex', ['male', 'female'])->nullable();
    $table->date('birth_date')->nullable();
    $table->string('contact_number', 30)->nullable();
    $table->string('address_line')->nullable();
    $table->string('city', 100)->nullable();
    $table->string('province', 100)->nullable();
    $table->string('program_or_position', 150)->nullable();
    $table->string('year_level', 30)->nullable();
    $table->string('photo_path')->nullable();
    $table->string('emergency_contact_name', 150)->nullable();
    $table->string('emergency_contact_number', 30)->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
    $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
