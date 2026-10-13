<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->bigIncrements('visitor_id');
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('contact_number', 30)->nullable();
            $table->string('email')->nullable();
            $table->enum('id_type', ['drivers_license', 'national_id', 'company_id', 'school_id', 'passport', 'other'])->nullable();
            $table->string('id_number', 100)->nullable();
            $table->string('affiliation', 150)->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->string('blacklist_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};