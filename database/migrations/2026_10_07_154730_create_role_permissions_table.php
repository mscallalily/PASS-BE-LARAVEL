<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
    $table->unsignedInteger('role_id');
    $table->unsignedInteger('permission_id');
    $table->timestamp('granted_at')->useCurrent();

    $table->primary(['role_id', 'permission_id']);
    $table->foreign('role_id')->references('role_id')->on('roles')->cascadeOnDelete();
    $table->foreign('permission_id')->references('permission_id')->on('permissions')->cascadeOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
