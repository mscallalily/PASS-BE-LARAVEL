<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_types', function (Blueprint $table) {
            $table->increments('violation_type_id');
            $table->string('violation_code', 30)->unique();
            $table->string('violation_name', 150);
            $table->string('description')->nullable();
            $table->decimal('default_penalty', 10, 2)->default(0);
            $table->enum('severity', ['minor', 'major', 'severe'])->default('minor');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_types');
    }
};