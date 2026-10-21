<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('registration_category', 20)->nullable();
            $table->string('registration_unit', 150)->nullable();
            $table->string('school_id_check_status', 32)->nullable();
            $table->json('school_id_check_result')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', fn (Blueprint $table) => $table->dropColumn(['registration_category', 'registration_unit', 'school_id_check_status', 'school_id_check_result']));
    }
};
