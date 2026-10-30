<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BarrierHardwareController extends Controller
{
    private function reader(Request $request): object
    {
        $reader = DB::table('rfid_readers')->where('reader_code', (string) $request->header('X-Reader-Code'))->first();
        abort_unless($reader && $reader->api_key_hash && Hash::check((string) $request->header('X-Reader-Key'), $reader->api_key_hash), 401);
        abort_if($reader->reader_status === 'maintenance', 423, 'Reader is under maintenance.');
        return $reader;
    }
    private function gate(object $reader): object
    {
        $gate = DB::table('access_gates')->where('gate_id', $reader->gate_id)->first();
        abort_unless($gate && $gate->gate_status === 'online', 423, 'Reader gate is unavailable.');
        return $gate;
    }
    public function claim(Request $request)
    {
        $reader = $this->reader($request); $gate = $this->gate($reader);
        $data = $request->validate(['poll' => 'required|accepted']);
        $command = DB::transaction(function () use ($reader, $gate) {
            DB::table('barrier_commands')->where('gate_id', $gate->gate_id)->where('command_status', 'pending')
                ->where('expires_at', '<=', now())->update(['command_status' => 'expired', 'receipt' => null, 'updated_at' => now()]);
            $query = DB::table('barrier_commands as c')->join('access_events as e', 'e.event_id', '=', 'c.event_id')
                ->where('c.gate_id', $gate->gate_id)->where('c.command_status', 'pending')->where('c.expires_at', '>', now())
                ->where('e.auth_result', 'granted')->where(function ($q) use ($reader) {
                    $q->whereNull('e.reader_id')->orWhere('e.reader_id', $reader->reader_id);
                });
            if ($reader->reader_direction !== 'both') $query->where('e.direction', $reader->reader_direction);
            if ($gate->gate_type !== 'both') $query->where('e.direction', $gate->gate_type === 'entry' ? 'in' : 'out');
            $row = $query->orderBy('c.command_id')->lockForUpdate()->first(['c.*']);
            if (!$row) return null;
            // A claimed command is never re-delivered, including after a lost HTTP response.
            DB::table('barrier_commands')->where('command_id', $row->command_id)->update([
                'command_status' => 'claimed', 'claimed_by_reader_id' => $reader->reader_id, 'claimed_at' => now(), 'updated_at' => now(),
            ]);
            return ['command_id' => (int) $row->command_id, 'receipt' => $row->receipt];
        }, 3);
        return response()->json(['command' => $command])->header('Cache-Control', 'no-store');
    }
    public function acknowledge(Request $request, int $id)
    {
        $reader = $this->reader($request);
        $data = $request->validate(['acknowledged' => 'required|boolean', 'error' => 'nullable|string|max:500']);
        $row = DB::table('barrier_commands')->where('command_id', $id)->first();
        abort_unless($row && (int) $row->gate_id === (int) $reader->gate_id && (int) $row->claimed_by_reader_id === (int) $reader->reader_id, 403);
        if ($row->command_status === 'claimed') {
            DB::table('barrier_commands')->where('command_id', $id)->where('command_status', 'claimed')->update([
                'command_status' => $data['acknowledged'] ? 'acknowledged' : 'failed', 'receipt' => null,
                'finished_at' => now(), 'last_error' => $data['acknowledged'] ? null : ($data['error'] ?? 'Opening was not confirmed.'), 'updated_at' => now(),
            ]);
        }
        return response()->json(['recorded' => true]);
    }
    public function status(Request $request, int $id)
    {
        abort_unless($request->user()?->canManageParking(), 403);
        $row = DB::table('barrier_commands')->where('command_id', $id)->first();
        abort_unless($row, 404);
        $status = $row->command_status;
        if (in_array($status, ['pending', 'claimed'], true) && $row->expires_at <= now()->toDateTimeString()) $status = $status === 'pending' ? 'expired' : 'unconfirmed';
        return response()->json(['command_id' => (int) $row->command_id, 'status' => $status])->header('Cache-Control', 'private, no-store');
    }
    public function verify(Request $request)
    {
        $reader = $this->reader($request);
        $data = $request->validate(['receipt' => 'required|string|max:8192']);
        try { $proof = json_decode(Crypt::decryptString($data['receipt']), true, 16, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { abort(422, 'Invalid gate approval.'); }
        abort_unless(is_array($proof) && ($proof['version'] ?? null) === 1
            && is_string($proof['nonce'] ?? null) && preg_match('/^[a-f0-9-]{36}$/D', $proof['nonce'])
            && is_int($proof['expires'] ?? null) && $proof['expires'] >= time() && $proof['expires'] <= time() + 60
            && in_array($proof['direction'] ?? null, ['in', 'out'], true), 422, 'Gate approval expired or invalid.');
        $gate = $this->gate($reader);
        abort_unless((int) $gate->gate_id === (int) ($proof['gate_id'] ?? 0), 423, 'Approval is for a different gate.');
        abort_unless($gate->gate_type === 'both' || $gate->gate_type === ($proof['direction'] === 'in' ? 'entry' : 'exit'), 422);
        $command = DB::table('barrier_commands')->where('nonce', $proof['nonce'])->first();
        abort_unless($command && $command->command_status === 'claimed' && (int) $command->claimed_by_reader_id === (int) $reader->reader_id
            && (int) $command->event_id === (int) ($proof['event_id'] ?? 0) && $command->expires_at > now()->toDateTimeString(), 422, 'No current command assigned to this controller.');
        $event = DB::table('access_events')->where('event_id', $proof['event_id'] ?? 0)->first();
        abort_unless($event && $event->auth_result === 'granted' && $event->direction === $proof['direction']
            && (int) $event->gate_id === (int) $gate->gate_id, 422, 'No matching authorized gate event.');
        if (($proof['actor_id'] ?? null) !== null) {
            $actor = User::where('user_id', $proof['actor_id'])->first();
            abort_unless($actor && $actor->canManageParking() && (int) $event->processed_by_user_id === (int) $actor->user_id, 403);
        } else {
            abort_unless((int) $event->reader_id === (int) $reader->reader_id, 403);
        }
        return response()->json(['command' => 'O', 'receipt_id' => $proof['nonce'], 'expires' => $proof['expires']])->header('Cache-Control', 'no-store');
    }
}
