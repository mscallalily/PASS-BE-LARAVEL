<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->string('notification_type', 50);             // violation_issued, pass_expiring, slot_full
            $table->string('title');
            $table->text('body');
            $table->json('payload')->nullable();                 // deep link target
            $table->string('related_type', 100)->nullable();     // polymorphic subject
            $table->unsignedBigInteger('related_id')->nullable();
            $table->enum('channel', ['push', 'in_app', 'email', 'sms'])->default('in_app');
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};