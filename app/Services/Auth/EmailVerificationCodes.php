<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\PassEmailVerificationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class EmailVerificationCodes
{
    public function send(User $user): void
    {
        // Logging/array transports never deliver to an inbox. Never report these as sent.
        if (!app()->environment('testing') && in_array(config('mail.default'), ['log', 'array', 'failover'], true)) {
            throw new RuntimeException('Configure a delivering mail transport for email verification.');
        }
        $code = (string) random_int(100000, 999999);
        $hash = Hash::make($code);
        DB::transaction(function () use ($user, $hash) {
            User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $previous = DB::table('email_verification_codes')->where('user_id', $user->getKey())->first();
            if ($previous && now()->diffInSeconds(\Carbon\Carbon::parse($previous->sent_at), true) < 60) {
                throw ValidationException::withMessages(['email' => 'Wait 60 seconds before requesting another code.']);
            }
            DB::table('email_verification_codes')->updateOrInsert(['user_id' => $user->getKey()], [
                'code_hash' => $hash, 'attempts' => 0,
                'expires_at' => now()->addMinutes(10), 'sent_at' => now(),
            ]);
        });
        try {
            $user->notify(new PassEmailVerificationNotification($code));
        } catch (Throwable $exception) {
            DB::table('email_verification_codes')->where('user_id', $user->getKey())->where('code_hash', $hash)->delete();
            throw $exception;
        }
    }

    public function verify(string $email, string $code): ?User
    {
        return DB::transaction(function () use ($email, $code) {
            $user = User::where('email', $email)->lockForUpdate()->first();
            if (!$user) return null;
            $record = DB::table('email_verification_codes')->where('user_id', $user->getKey())->lockForUpdate()->first();
            if (!$record || $record->attempts >= 5 || now()->gte(\Carbon\Carbon::parse($record->expires_at))) return null;
            if (!Hash::check($code, $record->code_hash)) {
                DB::table('email_verification_codes')->where('user_id', $user->getKey())->increment('attempts');
                return null;
            }
            $user->markEmailAsVerified();
            DB::table('email_verification_codes')->where('user_id', $user->getKey())->delete();
            return $user;
        });
    }
}
