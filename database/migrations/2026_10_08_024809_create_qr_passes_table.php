<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_passes', function (Blueprint $table) {
            $table->bigIncrements('qr_pass_id');
            $table->char('pass_code', 36)->unique();                       // uuid printed into the QR
            $table->string('token_hash', 128)->unique();                   // hashed secret, never stored raw
            $table->enum('pass_type', ['visitor', 'user_temporary']);
            $table->unsignedBigInteger('request_id')->nullable();
            $table->unsignedBigInteger('visitor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();             // fallback pass for a registered user
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');                               // expiry is mandatory
            $table->unsignedTinyInteger('max_uses')->default(2);           // one in and one out
            $table->unsignedTinyInteger('use_count')->default(0);
            $table->enum('pass_status', ['active', 'used', 'expired', 'revoked'])->default('active');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->dateTime('issued_at')->useCurrent();
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->timestamps();

            $table->foreign('request_id')->references('request_id')->on('visitor_requests');
            $table->foreign('visitor_id')->references('visitor_id')->on('visitors');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles');
            $table->foreign('issued_by')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_passes');
    }
};