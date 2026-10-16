<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VisitorRequestDocumentController extends Controller
{
    public function show(Request $request, int $id, string $kind)
    {
        abort_unless(in_array($kind, ['license', 'vehicle'], true), 404);
        $row = DB::table('visitor_requests as r')->join('visitors as v', 'v.visitor_id', '=', 'r.visitor_id')
            ->where('r.request_id', $id)->first(['r.driver_license_photo_path', 'r.vehicle_photo_path', 'v.user_id']);
        abort_unless($row, 404);
        $user = $request->user();
        abort_unless($user->canManageParking() || ($user->isGuest() && (int) $row->user_id === (int) $user->user_id), 403);
        $path = $kind === 'license' ? $row->driver_license_photo_path : $row->vehicle_photo_path;
        abort_unless($path && Storage::disk('private')->exists($path), 404, 'Document is not on file.');
        return response()->file(Storage::disk('private')->path($path), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
