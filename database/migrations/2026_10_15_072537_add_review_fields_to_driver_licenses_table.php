<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_licenses', function (Blueprint $table) {
            $table->enum('review_status', [
                'pending',
                'verified',
                'rejected',
            ])->default('pending');

            $table->text('review_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('driver_licenses', function (Blueprint $table) {
            $table->dropColumn([
                'review_status',
                'review_notes',
            ]);
        });
    }
};