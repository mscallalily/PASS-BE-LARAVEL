<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SystemSettingsController extends Controller
{
    private const GENERAL_KEYS = [
        'system_name',
        'institution_name',
        'contact_email',
        'contact_phone',
        'timezone',
        'date_format',
    ];

    public function index(Request $request): JsonResponse
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $settings = DB::table('system_settings')
            ->whereIn('setting_key', self::GENERAL_KEYS)
            ->pluck('setting_value', 'setting_key');

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        // Only administrators should change system-wide settings.
        // Adjust this role check if your User model uses a different role field.
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = Validator::make($request->all(), [
            'system_name' => 'required|string|max:150',
            'institution_name' => 'required|string|max:150',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'timezone' => 'required|in:Asia/Manila,UTC',
            'date_format' => 'required|in:MM/DD/YYYY,DD/MM/YYYY,YYYY-MM-DD',
        ])->validate();

        DB::transaction(function () use ($validated, $user) {
            foreach ($validated as $key => $value) {
                DB::table('system_settings')->updateOrInsert(
                    ['setting_key' => $key],
                    [
                        'setting_value' => $value ?? '',
                        'data_type' => 'string',
                        'setting_group' => 'general',
                        'is_public' => false,
                        'updated_by' => $user->user_id,
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'General settings saved successfully.',
        ]);
    }
    // The staging route existed without an implementation. Keep the contract
    // explicit until authenticated logo upload is developed and reviewed.
    public function uploadLogo(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        return response()->json(['message' => 'Logo upload is not implemented yet.'], 501);
    }
}
