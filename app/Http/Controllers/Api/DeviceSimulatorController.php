<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Parking\ParkingAccessService;
use App\Services\Parking\VisitorPassPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Development-only stand-in for the gate's RFID reader and QR scanner
 * (config/pass.php). Each scan is processed by ParkingAccessService exactly
 * like a real device scan: the same identification, authorization, license,
 * credential and slot-assignment rules, and the same database records.
 */
class DeviceSimulatorController extends Controller
{
    public function __construct(private ParkingAccessService $access)
    {
    }

    /** Null when the user may use the simulator; otherwise a 404 that hides it. */
    public static function unavailable(?User $user): ?JsonResponse
    {
        $allowed = config('pass.simulator.enabled')
            && !app()->environment('production')
            && $user
            && $user->canManageParking()
            && in_array($user->user_code, config('pass.simulator.testers', []), true);

        return $allowed ? null : response()->json(['message' => 'Not found.'], 404);
    }

    /** Credentials a tester can scan: active RFID assignments and visitor passes. */
    public function index(Request $request): JsonResponse
    {
        if ($hidden = self::unavailable($request->user())) {
            return $hidden;
        }

        $inside = DB::table('parking_transactions')->where('transaction_status', 'active')->pluck('vehicle_id')->flip();

        $rfid = DB::table('rfid_assignments as a')
            ->join('rfid_tags as t', 't.rfid_id', '=', 'a.rfid_id')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 'a.user_id')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'a.vehicle_id')
            ->where('a.assignment_status', 'active')
            ->orderBy('t.tag_uid')
            ->get(['t.tag_uid', 'u.user_code', 'u.user_type', DB::raw("CONCAT_WS(' ', up.first_name, up.last_name) as name"), 'v.vehicle_id', 'v.plate_number'])
            ->map(fn ($r) => ['credential' => $r->tag_uid, 'holder' => trim($r->name) ?: $r->user_code, 'user_type' => $r->user_type, 'plate_number' => $r->plate_number, 'inside' => isset($inside[$r->vehicle_id])]);

        $now = CarbonImmutable::now();
        $qr = DB::table('qr_passes as q')
            ->leftJoin('visitors as vis', 'vis.visitor_id', '=', 'q.visitor_id')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'q.vehicle_id')
            ->where('q.pass_type', 'visitor')
            ->orderByDesc('q.qr_pass_id')
            ->limit(50)
            ->get(['q.*', DB::raw("CONCAT_WS(' ', vis.first_name, vis.last_name) as name"), 'v.plate_number'])
            ->map(fn ($p) => ['credential' => $p->pass_code, 'holder' => $p->name, 'plate_number' => $p->plate_number, 'status' => VisitorPassPolicy::displayStatus($p, $now), 'inside' => isset($inside[$p->vehicle_id])]);

        return response()->json(['enabled' => true, 'gate' => 'Main Entrance / Exit', 'rfid' => $rfid, 'qr' => $qr]);
    }

    /** Simulate one scan at the shared Main Entrance / Exit. */
    public function scan(Request $request): JsonResponse
    {
        if ($hidden = self::unavailable($request->user())) {
            return $hidden;
        }

        $data = $request->validate([
            'device' => ['required', Rule::in(['rfid', 'qr'])],
            'direction' => ['required', Rule::in(['in', 'out'])],
            'credential' => ['required', 'string', 'max:64'],
        ]);

        $credential = $data['device'] === 'rfid' ? ['rfid_tag_uid' => trim($data['credential'])] : ['qr_pass_code' => trim($data['credential'])];
        $context = ['gate_id' => DB::table('access_gates')->where('gate_code', 'MAIN-GATE')->value('gate_id'), 'source' => 'simulator'];
        $context = array_filter($context);

        $result = $data['direction'] === 'in'
            ? $this->access->enter($credential, $request->user(), $context)
            : $this->access->exit($credential, $request->user(), $context);

        return response()->json($result + ['simulated' => true]);
    }
}
