<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track the document-verification workflow separately from license_status
     * (which describes the license itself: valid, expired, suspended, revoked).
     */
    public function up(): void
    {
        Schema::table('driver_licenses', function (Blueprint $table) {
            $table->enum('verification_status', ['processing', 'needs_correction', 'pending_review', 'verified'])
                ->default('pending_review')
                ->after('license_status');
            $table->enum('verification_source', ['automatic', 'staff'])->nullable()->after('verification_status');
            $table->text('verification_reason')->nullable()->after('verification_source');
            $table->json('ocr_result')->nullable()->after('verification_reason');
            // Identifies the current automatic check; results from superseded runs are discarded.
            $table->uuid('verification_run_id')->nullable()->after('ocr_result');
            $table->string('original_file_name')->nullable()->after('license_image_path');
            $table->dateTime('submitted_at')->nullable()->after('verification_run_id');
            $table->dateTime('checked_at')->nullable()->after('submitted_at');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('checked_at');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');

            $table->index('verification_status');
            $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Licenses uploaded before automation have not been reviewed yet.
        DB::table('driver_licenses')->update([
            'verification_status' => 'pending_review',
            'submitted_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('driver_licenses', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['verification_status']);
            $table->dropColumn([
                'verification_status',
                'verification_source',
                'verification_reason',
                'ocr_result',
                'verification_run_id',
                'original_file_name',
                'submitted_at',
                'checked_at',
                'reviewed_by',
                'reviewed_at',
            ]);
        });
    }
};
