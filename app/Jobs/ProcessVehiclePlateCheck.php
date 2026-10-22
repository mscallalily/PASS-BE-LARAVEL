<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Compatibility for old queued payloads. Never reads photos or approves vehicles. */
class ProcessVehiclePlateCheck implements ShouldQueue
{
    use Queueable;
    public int $tries = 1;
    public int $timeout = 30;
    public function __construct(public int $authorizationId, public string $runId) {}
    public function handle(): void
    {
        foreach (['plate_check_status', 'plate_check_run_id', 'plate_check_message', 'plate_checked_at'] as $column) {
            if (!Schema::hasColumn('vehicle_authorizations', $column)) return;
        }
        DB::transaction(function () {
            $query = DB::table('vehicle_authorizations')->where('authorization_id', $this->authorizationId);
            $row = (clone $query)->lockForUpdate()->first();
            if (!$row || $row->authorization_status !== 'pending' || $row->reviewed_at
                || $row->plate_check_run_id !== $this->runId || $row->plate_check_status !== 'processing') return;
            $query->update(['plate_check_status' => 'retired', 'plate_check_run_id' => null,
                'plate_check_message' => 'The earlier background check was retired. Submit a matching vehicle photo or contact PASS staff.',
                'plate_checked_at' => now(), 'updated_at' => now()]);
            DB::table('audit_logs')->insert(['actor_label' => 'system:vehicle-review', 'action' => 'vehicle_plate_check_retired',
                'auditable_type' => 'vehicle_authorizations', 'auditable_id' => $this->authorizationId,
                'old_values' => json_encode(['authorization_status' => 'pending', 'plate_check_status' => 'processing']),
                'new_values' => json_encode(['authorization_status' => 'pending', 'plate_check_status' => 'retired']), 'created_at' => now()]);
        }, 3);
    }
    public function failed(?Throwable $error): void { $this->handle(); }
}
