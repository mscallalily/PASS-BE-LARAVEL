<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_tags', function (Blueprint $table) {
            $table->bigIncrements('rfid_id');
            $table->string('tag_uid', 50)->unique();          // value read from the card
            $table->enum('tag_type', ['card', 'sticker', 'keyfob'])->default('card');
            $table->string('serial_number', 100)->nullable();
            $table->string('batch_code', 50)->nullable();
            $table->enum('tag_status', ['available', 'assigned', 'lost', 'damaged', 'deactivated'])->default('available');
            $table->date('acquired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_tags');
    }
};