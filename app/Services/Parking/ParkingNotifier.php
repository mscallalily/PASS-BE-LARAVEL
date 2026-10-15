<?php

namespace App\Services\Parking;

use Illuminate\Support\Facades\DB;

/**
 * In-app notifications for Parking Staff, written to the existing
 * notifications table (one row per staff member), with de-duplication.
 */
class ParkingNotifier
{
    /**
     * @return int[] Active admins and parking/security staff.
     */
    public static function staffUserIds(): array
    {
        return DB::table('users as u')
            ->where('u.account_status', 'active')
            ->whereNull('u.deleted_at')
            ->where(function ($q) {
                $q->whereIn('u.user_type', ['security', 'admin'])
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('user_roles as ur')
                            ->join('roles as r', 'r.role_id', '=', 'ur.role_id')
                            ->whereColumn('ur.user_id', 'u.user_id')
                            ->where('r.is_active', true)
                            ->whereIn('r.role_code', ['admin', 'parking_admin', 'security']);
                    });
            })
            ->pluck('u.user_id')
            ->all();
    }

    /**
     * Notify all staff, unless an identical alert (same type and dedupe key)
     * was sent within $dedupeMinutes. Returns whether anything was sent.
     */
    public static function notifyStaff(
        string $type,
        string $title,
        string $body,
        string $relatedType,
        int $relatedId,
        array $payload = [],
        ?string $dedupeKey = null,
        int $dedupeMinutes = 0,
    ): bool {
        return self::notifyUsers(self::staffUserIds(), $type, $title, $body, $relatedType, $relatedId, $payload, $dedupeKey, $dedupeMinutes);
    }

    /**
     * @return int[] Active administrators (admin user type, or admin / parking_admin role).
     */
    public static function adminUserIds(): array
    {
        return DB::table('users as u')
            ->where('u.account_status', 'active')
            ->whereNull('u.deleted_at')
            ->where(fn ($q) => $q->where('u.user_type', 'admin')
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('user_roles as ur')
                    ->join('roles as r', 'r.role_id', '=', 'ur.role_id')
                    ->whereColumn('ur.user_id', 'u.user_id')
                    ->where('r.is_active', true)
                    ->whereIn('r.role_code', ['admin', 'parking_admin'])))
            ->pluck('u.user_id')
            ->all();
    }

    public static function notifyAdmins(string $type, string $title, string $body, string $relatedType, int $relatedId, array $payload = []): bool
    {
        return self::notifyUsers(self::adminUserIds(), $type, $title, $body, $relatedType, $relatedId, $payload);
    }

    /** Notify one account (e.g. a guest about their visitor request). */
    public static function notifyUser(int $userId, string $type, string $title, string $body, string $relatedType, int $relatedId, array $payload = []): bool
    {
        return self::notifyUsers([$userId], $type, $title, $body, $relatedType, $relatedId, $payload);
    }

    /**
     * Notify the given accounts, unless an identical alert (same type and
     * dedupe key) was sent within $dedupeMinutes.
     *
     * @param  int[]  $userIds
     */
    public static function notifyUsers(
        array $userIds,
        string $type,
        string $title,
        string $body,
        string $relatedType,
        int $relatedId,
        array $payload = [],
        ?string $dedupeKey = null,
        int $dedupeMinutes = 0,
    ): bool {
        $payload['dedupe_key'] = $dedupeKey ?? "{$relatedType}:{$relatedId}";

        if ($dedupeMinutes > 0) {
            $recent = DB::table('notifications')
                ->where('notification_type', $type)
                ->where('payload->dedupe_key', $payload['dedupe_key'])
                ->where('created_at', '>=', now()->subMinutes($dedupeMinutes))
                ->exists();

            if ($recent) {
                return false;
            }
        }

        $rows = array_map(fn ($userId) => [
            'user_id' => $userId,
            'notification_type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => json_encode($payload),
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'channel' => 'in_app',
            'is_read' => false,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], array_values(array_unique($userIds)));

        if ($rows) {
            DB::table('notifications')->insert($rows);
        }

        return (bool) $rows;
    }
}
