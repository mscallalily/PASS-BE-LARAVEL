<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Vehicles\VehiclePlateReader;
use Illuminate\Http\Request;

class VehiclePhotoReadController extends Controller
{
    public function __invoke(Request $request, VehiclePlateReader $reader)
    {
        $user = $request->user();
        abort_unless($user->roles()->whereIn('role_code', ['student', 'faculty', 'employee', 'guest'])->where('is_active', true)->exists(), 403);
        $request->validate(['vehicle_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120']]);
        $file = $request->file('vehicle_photo');
        $size = @getimagesize($file->getRealPath());
        if (! $size || $size[0] * $size[1] > 20000000) {
            return response()->json(['message' => 'Use an image with at most 20 megapixels.'], 422);
        }
        try {
            $data = $reader->read($file->getRealPath(), $file->getMimeType());
        } catch (\Throwable $e) {
            report($e);
            $data = ['status' => 'unavailable', 'plate_number' => null, 'candidates' => [], 'message' => 'Photo could not be read. Enter the plate manually.'];
        }

        return response()->json(['data' => $data])->header('Cache-Control', 'private, no-store');
    }
}
