<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parking\ParkingAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Endpoint for physical RFID gate readers registered in rfid_readers.
 * A reader authenticates with its reader_code and API key (stored hashed in
 * api_key_hash); each scan runs the same entry/exit workflow as the staff
 * gate screen. No reader hardware is simulated: without a registered reader
 * this endpoint simply rejects requests.
 */
class RfidReaderController extends Controller
{
    public function __construct(private ParkingAccessService $access)
    {
    }

    public function scan(Request $request): JsonResponse
    {
        $reader = DB::table('rfid_readers')->where('reader_code', (string) $request->header('X-Reader-Code'))->first();

        if (!$reader || !$reader->api_key_hash || !Hash::check((string) $request->header('X-Reader-Key'), $reader->api_key_hash)) {
            return response()->json(['message' => 'Unknown reader or invalid key.'], 401);
        }

        if ($reader->reader_status === 'maintenance') {
            return response()->json(['message' => 'Reader is under maintenance.'], 423);
        }

        $data = $request->validate([
            'tag_uid' => ['required', 'string', 'max:50'],
            'direction' => [Rule::requiredIf($reader->reader_direction === 'both'), 'nullable', Rule::in(['in', 'out'])],
        ]);

        $gate = DB::table('access_gates')->where('gate_id', $reader->gate_id)->first();
        $direction = $reader->reader_direction === 'both' ? $data['direction'] : $reader->reader_direction;
        if (!$gate || $gate->gate_status !== 'online') {
            return response()->json(['message' => 'The reader gate is unavailable.'], 423);
        }
        if ($gate->gate_type !== 'both' && $gate->gate_type !== ($direction === 'in' ? 'entry' : 'exit')) {
            return response()->json(['message' => 'This gate does not allow the requested direction.'], 422);
        }

        DB::table('rfid_readers')->where('reader_id', $reader->reader_id)
            ->update(['last_heartbeat_at' => now(), 'reader_status' => 'online', 'updated_at' => now()]);

        $context = ['gate_id' => $reader->gate_id, 'reader_id' => $reader->reader_id, 'source' => 'reader'];
        $credential = ['rfid_tag_uid' => $data['tag_uid']];

        $result = $direction === 'in'
            ? $this->access->enter($credential, null, $context)
            : $this->access->exit($credential, null, $context);

        return response()->json($result);
    }
}
