<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ActivityAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile.department');
        $p = $user->profile;
        return response()->json(['data' => [
            'name' => implode(' ', array_filter([$p?->first_name, $p?->middle_name, $p?->last_name, $p?->suffix])),
            'email' => $user->email, 'id_number' => $user->user_code,
            'department' => $p?->registration_unit ?: $p?->department?->department_name,
            'contact_number' => $p?->contact_number ?? '', 'account_status' => $user->account_status,
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contact_number' => ['required', 'string', 'regex:/^(?:09\d{9}|\+639\d{9})$/'],
            ...array_fill_keys(['name', 'first_name', 'last_name', 'email', 'user_code', 'department_id', 'role', 'user_type', 'account_status'], ['prohibited']),
        ]);
        DB::transaction(function () use ($request, $data) {
            $user = $request->user();
            DB::table('users')->where('user_id', $user->user_id)->lockForUpdate()->first();
            $profile = $user->profile()->firstOrFail();
            $before = ['contact_number' => $profile->contact_number];
            $profile->update($data);
            DB::table('visitors')->where('user_id', $user->user_id)->update(['contact_number' => $data['contact_number'], 'updated_at' => now()]);
            ActivityAudit::record($request, $user, 'profile_contact_updated', 'users', $user->user_id, $before, $data);
        });
        return response()->json(['message' => 'Contact number saved.']);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:1024'],
            'password' => ['bail', 'string', new \App\Rules\PasswordBytes, 'required', 'string', 'min:8', 'max:72', 'confirmed', 'different:current_password'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $user = \App\Models\User::whereKey($request->user()->user_id)->lockForUpdate()->firstOrFail();
            if (!Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
            }
            $user->forceFill(['password' => $data['password'], 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
            $user->tokens()->delete();
            ActivityAudit::record($request, $user, 'password_changed', 'users', $user->user_id);
        });
        return response()->json(['message' => 'Password changed. Sign in again on your devices.']);
    }
}
