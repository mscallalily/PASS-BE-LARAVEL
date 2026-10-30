<?php
namespace App\Http\Middleware;

use App\Services\Parking\BarrierReceipt;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffBarrierReceipt
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!$response instanceof JsonResponse || !$response->isSuccessful() || !$request->user()?->canManageParking()) return $response;
        $data = $response->getData(true);
        if (!is_array($data)) return $response;
        $direction = str_ends_with($request->path(), '/parking/entry') ? 'in' : 'out';
        // An unavailable controller never turns a committed action into an API retry.
        try {
            if (str_contains($request->path(), 'vehicle-releases/')) {
                if ($request->input('action') !== 'release') return $response;
                $release = DB::table('vehicle_release_requests')->where('release_id', (int) $request->route('id'))->first();
                if (!$release || $release->status !== 'used' || (int) $release->released_by !== (int) $request->user()->user_id) return $response;
                $data['status'] = 'completed'; $data['transaction_id'] = (int) $release->transaction_id;
                $data['release_id'] = (int) $release->release_id; $data['gate_action'] = 'open';
            }
            $data = array_replace($data, BarrierReceipt::issue($data, $direction, (int) $request->user()->user_id));
        } catch (\Throwable $error) {
            report($error);
            $data['barrier_receipt'] = null; $data['barrier_command_id'] = null; $data['barrier_status'] = 'unavailable';
        }
        $response->setData($data);
        return $response;
    }
}
