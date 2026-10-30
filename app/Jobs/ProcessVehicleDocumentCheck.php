<?php

namespace App\Jobs;

use App\Models\VehicleAuthorization;
use App\Services\Vehicles\VehicleApprovalEligibility;
use App\Services\Vehicles\VehicleDocumentCheck;
use App\Services\Vehicles\VehicleDocumentRules;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Automatic OR/CR check for one campus vehicle authorization.
 *
 * Each submission gets a new run ID; this job writes only while its run is
 * still current and "processing", so a delayed result for an older upload can
 * never overwrite a newer submission. Approval happens only when the document
 * check passes AND every account/driver requirement passes; otherwise the
 * authorization stays pending (not approved) with the reasons for the owner.
 * There is no manual approval queue for these submissions.
 */
class ProcessVehicleDocumentCheck implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 240;

    /** $result: the check already run on these same documents before the registration was saved. */
    public function __construct(public int $authorizationId, public string $runId, public ?array $result = null)
    {
    }

    public function handle(VehicleDocumentCheck $check, VehicleApprovalEligibility $eligibility): void
    {
        $authorization = VehicleAuthorization::find($this->authorizationId);
        if (!$this->isCurrent($authorization)) return;
        $vehicle = DB::table('vehicles as v')->join('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->where('v.vehicle_id', $authorization->vehicle_id)->first(['v.*', 'vt.type_code']);
        if (!$vehicle || !$vehicle->owner_user_id) return; // Campus vehicles only; guest vehicles never reach this job.

        $result = $this->result ?? $check->run(['or' => $vehicle->or_document_path, 'cr' => $vehicle->cr_document_path], [
            'plate' => $vehicle->plate_number, 'make' => $vehicle->make, 'model' => $vehicle->model,
            'color' => $vehicle->color, 'year' => $vehicle->year_model, 'type_code' => $vehicle->type_code,
        ]);

        $this->store($authorization, $result, $eligibility);
    }

    /** An unexpected crash must not leave the submission stuck in "processing" or approve it. */
    public function failed(?Throwable $exception): void
    {
        $authorization = VehicleAuthorization::find($this->authorizationId);
        if ($this->isCurrent($authorization)) {
            $this->store($authorization, [
                'decision' => 'unavailable', 'reasons' => ['The automatic document check could not finish. Your vehicle remains unapproved. Retry the check later.'],
                'checks' => [], 'extracted' => [], 'policy_version' => VehicleDocumentRules::POLICY_VERSION,
                'approval_basis' => 'ocr_document_consistency', 'official_verification' => 'unavailable', 'ownership_verified' => false,
            ], app(VehicleApprovalEligibility::class));
        }
    }

    private function isCurrent(?VehicleAuthorization $authorization): bool
    {
        return $authorization
            && $authorization->document_check_run_id === $this->runId
            && $authorization->document_check_status === 'processing'
            && $authorization->authorization_status === 'pending';
    }

    /** Compare-and-swap: only the current run may leave "processing". */
    private function store(VehicleAuthorization $authorization, array $result, VehicleApprovalEligibility $eligibility): void
    {
        $approved = false;
        DB::transaction(function () use ($authorization, $result, $eligibility, &$approved) {
            $approved = false;
            $locked = VehicleAuthorization::lockForUpdate()->find($authorization->authorization_id);
            if (!$this->isCurrent($locked)) return;

            $decision = $result['decision'];
            $reasons = $result['reasons'];
            $approve = false;
            if ($decision === 'passed') {
                // Document consistency alone never approves: account, driver license and photo rules apply too.
                $blockers = $eligibility->approvalBlockers($locked);
                if ($blockers) {
                    $decision = 'needs_correction';
                    $reasons = $blockers;
                } else {
                    $approve = $approved = true;
                }
            }
            $result['final_decision'] = $approve ? 'approved' : $decision;

            $old = $locked->only(['authorization_status', 'document_check_status', 'remarks', 'reviewed_by', 'reviewed_at']);
            $expiry = $result['extracted']['or']['registration_expiry'] ?? null;
            $locked->forceFill([
                'authorization_status' => $approve ? 'approved' : 'pending',
                'document_check_status' => $approve ? 'passed' : $decision,
                'document_check_result' => json_encode($result + ['blocking_reasons' => $approve ? [] : $reasons]),
                'document_checked_at' => now(),
                'approval_source' => $approve ? 'automatic' : null,
                'reviewed_by' => null,
                'reviewed_at' => $approve ? now() : null,
                // remarks is VARCHAR(255); the full reasons are kept in document_check_result.
                'remarks' => mb_strimwidth($approve
                    ? 'Approved automatically: OR/CR plate and vehicle details matched'.($expiry ? "; registration valid until {$expiry}" : '').'. Authenticity and ownership not verified.'
                    : implode(' ', $reasons), 0, 255, '…'),
            ])->save();

            DB::table('audit_logs')->insert([
                'user_id' => null, 'actor_label' => 'system:vehicle-document-check',
                'action' => $approve ? 'authorization_auto_approved' : 'vehicle_document_check_'.$decision,
                'auditable_type' => VehicleAuthorization::class, 'auditable_id' => $locked->authorization_id,
                'old_values' => json_encode($old),
                'new_values' => json_encode($locked->only(['authorization_status', 'document_check_status', 'remarks']) + ['policy_version' => $result['policy_version']]),
                'created_at' => now(),
            ]);

            $ownerId = DB::table('vehicles')->where('vehicle_id', $locked->vehicle_id)->value('owner_user_id');
            $plate = DB::table('vehicles')->where('vehicle_id', $locked->vehicle_id)->value('plate_number');
            [$type, $title, $body] = match (true) {
                $approve => ['vehicle_auto_approved', 'Vehicle approved', "{$plate} was approved automatically after the OR/CR check. Document authenticity and ownership were not verified. RFID assignment remains separate."],
                $decision === 'unavailable' => ['vehicle_document_check_unavailable', 'Vehicle check could not finish', "The automatic OR/CR check for {$plate} could not finish. Your vehicle remains unapproved. Retry the check from My Vehicles."],
                $decision === 'retake_required' => ['vehicle_document_retake', 'Retake vehicle documents', "{$plate} needs clearer OR/CR photos: ".implode(' ', $reasons)],
                default => ['vehicle_document_correction', 'Vehicle registration needs correction', "{$plate} could not be approved: ".implode(' ', $reasons)],
            };
            if ($ownerId) {
                DB::table('notifications')->insert([
                    'user_id' => $ownerId, 'notification_type' => $type, 'title' => $title, 'body' => mb_substr($body, 0, 1000),
                    'payload' => json_encode(['screen' => 'vehicles', 'vehicle_id' => $locked->vehicle_id]),
                    'related_type' => VehicleAuthorization::class, 'related_id' => $locked->authorization_id,
                    'channel' => 'in_app', 'is_read' => false, 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }, 3);

        // After approval commits: a new registration receives an available inventory
        // sticker. Without one it stays approved and shows Pending RFID Assignment.
        if ($approved) {
            try {
                app(\App\Services\Rfid\RfidInventory::class)->assignToVehicle($authorization->vehicle_id);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
