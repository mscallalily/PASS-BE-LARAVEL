<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visitor_requests', function (Blueprint $table) {
            $table->string('driver_license_photo_path')->nullable();
            $table->string('vehicle_photo_path')->nullable();
            $table->unsignedBigInteger('documents_reviewed_by')->nullable();
            $table->timestamp('documents_reviewed_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('visitor_requests', fn (Blueprint $table) => $table->dropColumn([
            'driver_license_photo_path', 'vehicle_photo_path', 'documents_reviewed_by', 'documents_reviewed_at',
        ]));
    }
};
