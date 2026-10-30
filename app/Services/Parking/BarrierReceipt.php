<?php
namespace App\Services\Parking;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Queue one short-lived physical OPEN only after PASS commits approval. */
class BarrierReceipt
{
    public static function issue(array $result, string $direction, ?int $actorId): array
    {
        $none = ['barrier_receipt' => null, 'barrier_command_id' => null, 'barrier_status' => 'not_requested'];
        $expected = $direction === 'in' ? 'granted' : 'completed';
        if (($result['status'] ?? null) !== $expected || empty($result['transaction_id'])) return $none;
        $transaction = DB::table('parking_transactions')->where('transaction_id', $result['transaction_id'])->first();
        if (!$transaction) return $none;
        $eventId = $direction === 'in' ? $transaction->entry_event_id : $transaction->exit_event_id;
        $event = $eventId ? DB::table('access_events')->where('event_id', $eventId)->first() : null;
        if (!$event || $event->auth_result !== 'granted' || $event->direction !== $direction
            || (int) $event->vehicle_id !== (int) $transaction->vehicle_id
            || ($actorId !== null && (int) $event->processed_by_user_id !== $actorId)) return $none;
        // PASS_PARKING_STATION_V1: this station has no working servo; never queue an OPEN.
        if (DB::table('parking_slot_stations as station')->join('rfid_readers as reader', 'reader.reader_id', '=', 'station.reader_id')
            ->where('reader.gate_id', $event->gate_id)->exists()) {
            return ['barrier_receipt' => null, 'barrier_command_id' => null, 'barrier_status' => 'unavailable'];
        }
        $nonce = (string) Str::uuid();
        $expires = now()->addSeconds(15);
        $receipt = Crypt::encryptString(json_encode([
            'version' => 1, 'nonce' => $nonce, 'event_id' => (int) $eventId,
            'gate_id' => (int) $event->gate_id, 'direction' => $direction,
            'actor_id' => $actorId, 'expires' => $expires->getTimestamp(),
        ], JSON_THROW_ON_ERROR));
        // Unique event_id prevents another command for the same committed event.
        $inserted = DB::table('barrier_commands')->insertOrIgnore([
            'event_id' => $eventId, 'gate_id' => $event->gate_id, 'nonce' => $nonce,
            'receipt' => $receipt, 'command_status' => 'pending', 'expires_at' => $expires,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if (!$inserted) return $none;
        $id = DB::table('barrier_commands')->where('nonce', $nonce)->value('command_id');
        return ['barrier_receipt' => $receipt, 'barrier_command_id' => (int) $id, 'barrier_status' => 'pending'];
    }
}
