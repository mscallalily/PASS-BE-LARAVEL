<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parking\ParkingNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** Fixed slot occupancy is separate from gate permission and staff identity confirmation. */
class ParkingStationController extends Controller
{
    private const ONLINE_SECONDS = 12;

    private function reader(Request $request): object
    {
        $reader = DB::table('rfid_readers as r')->join('access_gates as g', 'g.gate_id', '=', 'r.gate_id')
            ->where('r.reader_code', (string) $request->header('X-Reader-Code'))
            ->first(['r.*', 'g.gate_status', 'g.gate_type', 'g.parking_area_id as gate_area_id']);
        abort_unless($reader && $reader->api_key_hash && Hash::check((string) $request->header('X-Reader-Key'), $reader->api_key_hash), 401, 'Invalid reader credentials.');
        abort_if($reader->reader_status === 'maintenance' || $reader->gate_status !== 'online'
            || !in_array($reader->reader_direction, ['in', 'both'], true) || !in_array($reader->gate_type, ['entry', 'both'], true), 423, 'Reader or gate is unavailable.');
        return $reader;
    }

    private function station(object $reader, bool $lock = false): object
    {
        $q = DB::table('parking_slot_stations')->where('reader_id', $reader->reader_id);
        $station = ($lock ? $q->lockForUpdate() : $q)->first();
        abort_unless($station, 409, 'Bind this reader to its physical sensor slot in the staff dashboard first.');
        $areaId = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->where('s.slot_id', $station->slot_id)->value('z.parking_area_id');
        abort_if($reader->gate_area_id !== null && (int) $areaId !== (int) $reader->gate_area_id, 403, 'Sensor slot is outside this gate area. Reconfigure the station.');
        return $station;
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canManageParking(), 403);
        $rows = DB::table('parking_slot_stations as p')->join('rfid_readers as r', 'r.reader_id', '=', 'p.reader_id')
            ->join('parking_slots as s', 's.slot_id', '=', 'p.slot_id')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
            ->get(['p.*', 'r.reader_code', 's.slot_code', 'z.zone_name', 'a.area_name']);
        foreach ($rows as $row) {
            $row->online = $row->last_seen_at && CarbonImmutable::parse($row->last_seen_at)->gte(now()->subSeconds(self::ONLINE_SECONDS));
            $row->display_state = $row->online ? $row->sensor_state : 'offline';
            $row->assignment = DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->where('t.slot_id', $row->slot_id)->where('t.transaction_status', 'active')
                ->first(['t.transaction_id', 'v.plate_number', 't.entry_datetime', 't.slot_confirmed_at', 't.slot_sensor_detected_at']);
            $row->arrival_detected = $row->online && $row->sensor_state === 'occupied' && $row->assignment
                && (int) $row->detected_transaction_id === (int) $row->assignment->transaction_id;
            foreach (['last_seen_at', 'state_changed_at', 'last_free_seen_at'] as $key) {
                $row->$key = $row->$key ? CarbonImmutable::parse($row->$key)->toIso8601String() : null;
            }
            unset($row->session_id, $row->last_sequence, $row->configured_by);
        }
        $slots = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
            ->where('a.is_active', true)->where('z.is_active', true)->where('s.slot_status', 'available')
            ->orderBy('a.area_code')->orderBy('z.zone_code')->orderBy('s.slot_code')
            ->get(['s.slot_id', 's.slot_code', 'z.zone_name', 'a.area_name', 'a.parking_area_id']);
        $readers = DB::table('rfid_readers as r')->join('access_gates as g', 'g.gate_id', '=', 'r.gate_id')
            ->whereIn('r.reader_direction', ['in', 'both'])->whereIn('g.gate_type', ['entry', 'both'])
            ->get(['r.reader_id', 'r.reader_code', 'g.gate_name', 'g.parking_area_id']);
        return response()->json(['data' => $rows, 'slots' => $slots, 'readers' => $readers,
            'can_configure' => !$request->user()->isAdmin(), 'servo_available' => false]);
    }

    public function bind(Request $request): JsonResponse
    {
        abort_unless($request->user()->canManageParking() && !$request->user()->isAdmin(), 403, 'Only Parking Staff can bind physical slot sensors.');
        $data = $request->validate(['reader_id' => ['required', 'integer', 'exists:rfid_readers,reader_id'], 'slot_id' => ['required', 'integer', 'exists:parking_slots,slot_id']]);
        return DB::transaction(function () use ($request, $data) {
            // Serialize configuration changes, including two staff choosing the same slot.
            DB::table('rfid_readers')->orderBy('reader_id')->lockForUpdate()->get(['reader_id']);
            $reader = DB::table('rfid_readers as r')->join('access_gates as g', 'g.gate_id', '=', 'r.gate_id')
                ->where('r.reader_id', $data['reader_id'])->first(['r.reader_direction', 'g.gate_type', 'g.parking_area_id']);
            $slot = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
                ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')->where('s.slot_id', $data['slot_id'])
                ->first(['s.slot_status', 'z.parking_area_id', 'z.is_active', 'a.is_active as area_active']);
            abort_unless(in_array($reader->reader_direction, ['in', 'both'], true) && in_array($reader->gate_type, ['entry', 'both'], true), 422, 'Use an entry or shared reader.');
            abort_unless($slot->is_active && $slot->area_active && $slot->slot_status === 'available', 422, 'Use an active parking slot.');
            abort_if($reader->parking_area_id !== null && (int) $reader->parking_area_id !== (int) $slot->parking_area_id, 422, 'The slot must belong to this gate area.');
            abort_if(DB::table('parking_slot_stations')->where('slot_id', $data['slot_id'])->where('reader_id', '!=', $data['reader_id'])->exists(), 422, 'This slot already has a sensor station.');
            $existing = DB::table('parking_slot_stations')->where('reader_id', $data['reader_id'])->first();
            if ($existing && (int) $existing->slot_id === (int) $data['slot_id']) {
                return response()->json(['message' => 'This fixed slot is already configured.']);
            }
            $values = ['slot_id' => $data['slot_id'], 'session_id' => null, 'last_sequence' => 0, 'sensor_state' => 'unknown',
                'distance_cm' => null, 'last_seen_at' => null, 'last_free_seen_at' => null, 'state_changed_at' => now(),
                'detected_transaction_id' => null, 'configured_by' => $request->user()->user_id, 'updated_at' => now()];
            if ($existing) DB::table('parking_slot_stations')->where('station_id', $existing->station_id)->update($values);
            else DB::table('parking_slot_stations')->insert($values + ['reader_id' => $data['reader_id'], 'created_at' => now()]);
            return response()->json(['message' => 'Fixed sensor slot saved. Place the sensor at this slot, then start the parking station.']);
        });
    }

    public function start(Request $request): JsonResponse
    {
        $reader = $this->reader($request);
        $data = $request->validate(['session_id' => ['required', 'uuid']]);
        return DB::transaction(function () use ($reader, $data) {
            $station = $this->station($reader, true);
            DB::table('parking_slot_stations')->where('station_id', $station->station_id)->update([
                'session_id' => $data['session_id'], 'oled_ready' => false, 'last_sequence' => 0, 'sensor_state' => 'unknown', 'distance_cm' => null,
                'last_seen_at' => null, 'last_free_seen_at' => null, 'detected_transaction_id' => null, 'state_changed_at' => now(), 'updated_at' => now(),
            ]);
            $slot = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
                ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')->where('s.slot_id', $station->slot_id)
                ->first(['s.slot_id', 's.slot_code', 'z.zone_name', 'a.area_name']);
            return response()->json(['slot' => $slot, 'cursor' => (int) (DB::table('access_events')->max('event_id') ?? 0)]);
        });
    }

    private function currentSession(object $reader, string $session, bool $lock = false): object
    {
        $station = $this->station($reader, $lock);
        abort_unless($station->session_id === $session, 409, 'Station session changed. Restart the parking station.');
        return $station;
    }

    public function poll(Request $request): JsonResponse
    {
        $reader = $this->reader($request);
        $data = $request->validate(['session_id' => ['required', 'uuid'], 'after_event_id' => ['required', 'integer', 'min:0']]);
        $this->currentSession($reader, $data['session_id']);
        $events = DB::table('access_events')->where('gate_id', $reader->gate_id)->where('event_id', '>', $data['after_event_id'])
            ->where(fn ($q) => $q->whereNull('reader_id')->orWhere('reader_id', $reader->reader_id))
            ->orderBy('event_id')->limit(50)->get(['event_id', 'direction', 'auth_result', 'event_datetime', 'access_method', 'raw_payload']);
        $assignment = null;
        $clear = false;
        foreach ($events as $event) {
            $raw = json_decode($event->raw_payload ?? '{}', true) ?? [];
            if (($raw['source'] ?? null) === 'simulator' || CarbonImmutable::parse($event->event_datetime)->lt(now()->subSeconds(30))) continue;
            if ($event->direction !== 'in' || $event->auth_result !== 'granted') {
                $assignment = null; $clear = true; continue;
            }
            $row = DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->join('parking_slots as s', 's.slot_id', '=', 't.slot_id')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
                ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
                ->where('t.entry_event_id', $event->event_id)->where('t.transaction_status', 'active')
                ->first(['t.transaction_id', 't.slot_id', 'v.plate_number', 's.slot_code', 'z.zone_name', 'a.area_name', 'a.parking_area_id']);
            if ($row && ($reader->gate_area_id === null || (int) $row->parking_area_id === (int) $reader->gate_area_id)) {
                // The most recent committed entry replaces earlier gate instructions.
                $assignment = (array) $row + ['event_id' => (int) $event->event_id];
                $clear = false;
            }
        }
        return response()->json(['cursor' => $events->isNotEmpty() ? (int) $events->last()->event_id : (int) $data['after_event_id'], 'assignment' => $assignment, 'clear_display' => $clear]);
    }

    public function observe(Request $request): JsonResponse
    {
        $reader = $this->reader($request);
        $data = $request->validate(['session_id' => ['required', 'uuid'], 'sequence' => ['required', 'integer', 'min:1', 'max:2147483647'], 'oled_ready' => ['required', 'boolean'],
            'state' => ['required', Rule::in(['available', 'occupied', 'unknown'])], 'distance_cm' => ['nullable', 'numeric', 'between:2,400']]);
        $distance = $data['distance_cm'] ?? null;
        abort_if($data['state'] === 'occupied' && ($distance === null || $distance > 15), 422, 'Occupied readings require a distance of 2-15 cm.');
        abort_if($data['state'] === 'available' && ($distance === null || $distance < 20), 422, 'Available readings require a distance of at least 20 cm.');
        return DB::transaction(function () use ($reader, $data, $distance) {
            DB::table('parking_areas')->orderBy('parking_area_id')->lockForUpdate()->get(['parking_area_id']);
            $station = $this->currentSession($reader, $data['session_id'], true);
            if ($data['sequence'] <= $station->last_sequence) return response()->json(['accepted' => false, 'duplicate' => true]);
            $changed = $station->sensor_state !== $data['state'];
            $txn = DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->where('t.slot_id', $station->slot_id)->where('t.transaction_status', 'active')->lockForUpdate()
                ->first(['t.*', 'v.plate_number']);
            $arrival = $changed && $data['state'] === 'occupied' && $station->sensor_state !== 'occupied'
                && $station->last_seen_at && CarbonImmutable::parse($station->last_seen_at)->gte(now()->subSeconds(self::ONLINE_SECONDS))
                && $txn && !$txn->slot_sensor_detected_at && $station->last_free_seen_at
                && CarbonImmutable::parse($station->last_free_seen_at)->gte(CarbonImmutable::parse($txn->entry_datetime)->subSeconds(self::ONLINE_SECONDS))
                && CarbonImmutable::parse($station->last_free_seen_at)->gte(now()->subSeconds(30));
            $values = ['last_sequence' => $data['sequence'], 'oled_ready' => $data['oled_ready'], 'sensor_state' => $data['state'], 'distance_cm' => $distance, 'last_seen_at' => now(), 'updated_at' => now()];
            if ($changed) $values['state_changed_at'] = now();
            if ($data['state'] === 'available') $values['detected_transaction_id'] = null;
            if ($data['state'] === 'available') $values['last_free_seen_at'] = now();
            if ($arrival) {
                $values['detected_transaction_id'] = $txn->transaction_id;
                DB::table('parking_transactions')->where('transaction_id', $txn->transaction_id)->whereNull('slot_sensor_detected_at')
                    ->update(['slot_sensor_detected_at' => now(), 'updated_at' => now()]);
            }
            DB::table('parking_slot_stations')->where('station_id', $station->station_id)->update($values);
            if ($changed && in_array($data['state'], ['occupied', 'available'], true)
                && !($data['state'] === 'occupied' && $station->detected_transaction_id && !$arrival)) {
                $location = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
                    ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')->where('s.slot_id', $station->slot_id)
                    ->first(['s.slot_code', 'a.area_name']);
                $body = "{$location->slot_code} ({$location->area_name}): sensor detected {$data['state']}.";
                if ($txn) $body .= " Assigned vehicle: {$txn->plate_number}.";
                $body .= $arrival ? ' Arrival detected at the assigned slot. Vehicle identity still requires staff verification.'
                    : ($data['state'] === 'occupied' ? ' Occupant identity is unverified.' : ' This does not record a vehicle exit.');
                ParkingNotifier::notifyStaff('parking_slot_sensor', $arrival ? 'Assigned slot arrival detected' : 'Parking slot sensor update', $body,
                    'parking_slot', (int) $station->slot_id, ['slot_id' => (int) $station->slot_id, 'station_id' => (int) $station->station_id,
                        'transaction_id' => $arrival ? (int) $txn->transaction_id : null, 'sensor_state' => $data['state'], 'identity_verified' => false]);
            }
            return response()->json(['accepted' => true, 'arrival_detected' => (bool) $arrival]);
        });
    }
}
