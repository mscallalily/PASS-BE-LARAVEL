<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE vehicles ADD CONSTRAINT chk_vehicle_one_owner CHECK ((owner_user_id IS NULL) <> (owner_visitor_id IS NULL))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vehicles DROP CHECK chk_vehicle_one_owner');
    }
};