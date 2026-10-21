<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_release_requests', function (Blueprint $table) {
            $table->bigIncrements('release_id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('owner_user_id');
            $table->unsignedBigInteger('requested_by');
            $table->string('driver_name', 200);
            $table->string('driver_contact', 30);
            $table->text('license_encrypted');
            $table->date('license_expiry');
            $table->text('reason');
            $table->text('exception_reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->dateTime('expires_at');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->unsignedBigInteger('exit_event_id')->nullable();
            $table->timestamps();
            $table->foreign('transaction_id')->references('transaction_id')->on('parking_transactions');
            foreach (['owner_user_id', 'requested_by', 'reviewed_by', 'released_by'] as $column) $table->foreign($column)->references('user_id')->on('users');
            $table->foreign('exit_event_id')->references('event_id')->on('access_events');
            $table->index(['transaction_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_release_requests'); }
};
