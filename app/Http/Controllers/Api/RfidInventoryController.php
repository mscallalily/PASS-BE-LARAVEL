<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Rfid\RfidInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Admin-only RFID inventory: register scanned stickers, view status and history, confirm issuance. */
class RfidInventoryController extends Controller
{
    public function __construct(private RfidInventory $inventory) {}

    /** Same rule as RfidAssignmentController: Admin only; Parking Staff gains nothing here. */
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->account_status === 'active' &&
            ($user->user_type === 'admin' || $user->roles()->whereIn('role_code', ['admin', 'parking_admin'])->where('is_active', true)->exists()), 403);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $tags = DB::table('rfid_tags as t')->leftJoin('users as r', 'r.user_id', '=', 't.inventory_registered_by')
            ->whereNotNull('t.inventory_registered_at')->orderBy('t.inventory_registered_at')->orderBy('t.rfid_id')
            ->get(['t.rfid_id', 't.tag_uid', 't.tag_status', 't.inventory_source', 't.inventory_registered_at', 'r.user_code as registered_by']);
        $history = DB::table('rfid_assignments as a')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'a.vehicle_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('user_profiles as p', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('users as i', 'i.user_id', '=', 'a.issued_by')
            ->whereIn('a.rfid_id', $tags->pluck('rfid_id'))->orderByDesc('a.assignment_id')
            ->get(['a.assignment_id', 'a.rfid_id', 'a.vehicle_id', 'a.assignment_status', 'a.assignment_source', 'a.assigned_at', 'a.issued_at', 'a.unassigned_at', 'a.remarks',
                'v.plate_number', 'u.user_code', 'u.user_type', DB::raw("TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) as user_name"), 'i.user_code as issued_by'])
            ->groupBy('rfid_id');
        $items = $tags->map(function ($tag) use ($history) {
            $assignments = $history->get($tag->rfid_id, collect())->values();
            $current = $assignments->firstWhere('assignment_status', 'active');
            $status = match ($tag->tag_status) {
                'assigned' => $current?->issued_at ? 'issued' : 'assigned',
                'deactivated' => 'revoked',
                default => $tag->tag_status,
            };
            return (array) $tag + ['status' => $status, 'current' => $current, 'history' => $assignments];
        });
        $pending = $this->inventory->pendingVehicles();
        return response()->json([
            'data' => $items->values(),
            'summary' => ['total' => $items->count()] + collect(['available', 'assigned', 'issued', 'lost', 'damaged', 'revoked'])
                ->mapWithKeys(fn ($s) => [$s => $items->where('status', $s)->count()])->all() + ['pending_vehicles' => $pending->count()],
            'pending' => $pending->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'tag_uid' => ['required', 'string', 'max:80'],
            'source' => ['required', Rule::in(['reader', 'manual'])],
        ]);
        $result = $this->inventory->register($data['tag_uid'], $data['source'], $request->user());
        $uid = RfidInventory::normalize($data['tag_uid']);
        return response()->json([
            'message' => $result['assigned_plate']
                ? "Sticker {$uid} registered and assigned to {$result['assigned_plate']}, which was waiting for RFID. Issue it to the owner."
                : "Sticker {$uid} registered as Available.",
        ] + $result, 201);
    }

    public function issue(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        $this->inventory->issue($id, $request->user());
        return response()->json(['message' => 'Hand-over confirmed. The sticker is now Issued.']);
    }

    public function retire(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['status' => ['required', Rule::in(['lost', 'damaged'])], 'replace' => ['sometimes', 'boolean']]);
        $result = $this->inventory->retire($id, $data['status'], (bool) ($data['replace'] ?? false), $request->user());
        $message = "Sticker marked {$data['status']}. Future scans of it will be denied.";
        if ($result['replacement_plate']) $message .= " A replacement sticker was assigned to {$result['replacement_plate']}; issue it to the owner.";
        elseif ($result['pending']) $message .= ' No sticker is available: the vehicle is Pending RFID Assignment until one is registered.';
        return response()->json(['message' => $message] + $result);
    }
}
