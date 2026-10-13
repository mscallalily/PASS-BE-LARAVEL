<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_violations', function (Blueprint $table) {
            $table->bigIncrements('violation_id');
            $table->unsignedInteger('violation_type_id');
            $table->unsignedBigInteger('parking_transaction_id')->nullable();   // when tied to a stay
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('visitor_id')->nullable();
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->unsignedInteger('parking_area_id')->nullable();
            $table->dateTime('occurred_at');
            $table->unsignedBigInteger('reported_by')->nullable();              // security personnel
            $table->dateTime('reported_at')->useCurrent();
            $table->text('description')->nullable();
            $table->decimal('penalty_amount', 10, 2)->nullable();               // may override the default
            $table->enum('violation_status', ['open', 'under_review', 'resolved', 'dismissed', 'escalated'])->default('open');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->string('resolution_remarks')->nullable();
            $table->timestamps();

            $table->foreign('violation_type_id')->references('violation_type_id')->on('violation_types');
            $table->foreign('parking_transaction_id')->references('transaction_id')->on('parking_transactions');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('visitor_id')->references('visitor_id')->on('visitors');
            $table->foreign('slot_id')->references('slot_id')->on('parking_slots');
            $table->foreign('parking_area_id')->references('parking_area_id')->on('parking_areas');
            $table->foreign('reported_by')->references('user_id')->on('users');
            $table->foreign('resolved_by')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_violations');
    }
};