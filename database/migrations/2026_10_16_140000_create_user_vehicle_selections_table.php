<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_vehicle_selections', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedBigInteger('vehicle_id');
            $table->timestamp('updated_at')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles');
        });
        // Preserve an existing parked vehicle first; otherwise choose the oldest
        // assigned vehicle. All future switches are explicit owner actions.
        foreach (DB::table('vehicles')->whereNotNull('owner_user_id')->distinct()->pluck('owner_user_id') as $owner) {
            $vehicle = DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->where('v.owner_user_id', $owner)->where('t.transaction_status', 'active')->orderBy('t.transaction_id')->value('v.vehicle_id');
            $vehicle ??= DB::table('vehicles as v')->join('rfid_assignments as a', 'a.vehicle_id', '=', 'v.vehicle_id')
                ->join('rfid_tags as tag', 'tag.rfid_id', '=', 'a.rfid_id')
                ->where('v.owner_user_id', $owner)->whereNull('v.deleted_at')->where('v.registration_status', 'registered')
                ->where('a.assignment_status', 'active')->whereNull('a.unassigned_at')->where('tag.tag_status', 'assigned')
                ->orderBy('v.vehicle_id')->value('v.vehicle_id');
            if ($vehicle) DB::table('user_vehicle_selections')->insert(['user_id' => $owner, 'vehicle_id' => $vehicle, 'updated_at' => now()]);
        }
    }
    public function down(): void { Schema::dropIfExists('user_vehicle_selections'); }
};
