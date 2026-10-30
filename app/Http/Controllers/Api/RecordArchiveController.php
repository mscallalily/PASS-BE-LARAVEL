<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class RecordArchiveController extends Controller
{
    public function index(Request $r, string $entity)
    {
        abort_unless($r->user()->isAdmin(), 403);
        $d = $r->validate(['state' => ['nullable', Rule::in(['active', 'archived'])], 'search' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $archived = ($d['state'] ?? 'archived') === 'archived';
        if ($entity === 'users') {
            $q = DB::table('users as u')->leftJoin('user_profiles as p', 'p.user_id', '=', 'u.user_id')
                ->selectRaw("u.user_id as id, u.user_code as identifier, u.email, u.account_status as status, TRIM(CONCAT_WS(' ', p.first_name, p.last_name, p.suffix)) as name, u.deleted_at as archived_at");
            if (!empty($d['search'])) $q->where(function ($q) use ($d) { foreach (['u.user_code', 'u.email', 'p.first_name', 'p.last_name'] as $c) $q->orWhere($c, 'like', '%'.$d['search'].'%'); });
            $column = 'u.deleted_at'; $order = 'u.user_id';
        } else {
            $q = DB::table('vehicles as v')->leftJoin('users as u', 'u.user_id', '=', 'v.owner_user_id')
                ->leftJoin('user_profiles as p', 'p.user_id', '=', 'u.user_id')
                ->leftJoin('visitors as visitor', 'visitor.visitor_id', '=', 'v.owner_visitor_id')
                ->selectRaw("v.vehicle_id as id, v.plate_number as identifier, u.user_code as owner_code, COALESCE(NULLIF(TRIM(CONCAT_WS(' ', p.first_name, p.last_name)), ''), NULLIF(TRIM(CONCAT_WS(' ', visitor.first_name, visitor.last_name)), ''), u.user_code) as owner, TRIM(CONCAT_WS(' ', v.make, v.model)) as name, v.registration_status as status, v.deleted_at as archived_at");
            if (!empty($d['search'])) $q->where(function ($q) use ($d) { foreach (['v.plate_number', 'u.user_code', 'p.first_name', 'p.last_name', 'visitor.first_name', 'visitor.last_name'] as $c) $q->orWhere($c, 'like', '%'.$d['search'].'%'); });
            $column = 'v.deleted_at'; $order = 'v.vehicle_id';
        }
        $summary = ['active' => (clone $q)->whereNull($column)->count(), 'archived' => (clone $q)->whereNotNull($column)->count()];
        $q->whereNull($column, 'and', $archived)->orderByDesc($order);
        return response()->json($q->paginate(20)->toArray() + ['summary' => $summary])->header('Cache-Control', 'private, no-store');
    }

    public function change(Request $r, string $entity, int $id, string $action)
    {
        abort_unless($r->user()->isAdmin(), 403);
        $reason = trim($r->validate(['reason' => ['required', 'string', 'min:5', 'max:500']])['reason']);
        abort_if(mb_strlen($reason) < 5, 422, 'Enter a reason with at least five characters.');
        $archive = $action === 'archive';
        DB::transaction(function () use ($r, $entity, $id, $archive, $reason) {
            // All lifecycle changes take the same admin locks to avoid concurrent last-admin removal.
            $admins = DB::table('users')->whereNull('deleted_at')->where('account_status', 'active')
                ->where(fn ($q) => $q->where('user_type', 'admin')->orWhereExists(fn ($roles) => $roles->selectRaw('1')->from('user_roles as ur')->join('roles as ro', 'ro.role_id', '=', 'ur.role_id')->whereColumn('ur.user_id', 'users.user_id')->whereIn('ro.role_code', ['admin', 'parking_admin'])->where('ro.is_active', true)))
                ->orderBy('user_id')->lockForUpdate()->get();
            $actor = User::find($r->user()->user_id);
            abort_unless($actor && $actor->isAdmin(), 403);
            $key = $entity === 'users' ? 'user_id' : 'vehicle_id';
            $target = DB::table($entity)->where($key, $id)->lockForUpdate()->first(); abort_unless($target, 404);
            abort_if($archive === ($target->deleted_at !== null), 409, $archive ? 'This record is already archived.' : 'This record is already active.');
            $vehicleIds = $entity === 'vehicles' ? [$id] : DB::table('vehicles as v')->leftJoin('visitors as vis', 'vis.visitor_id', '=', 'v.owner_visitor_id')
                ->where(fn ($q) => $q->where('v.owner_user_id', $id)->orWhere('vis.user_id', $id))->pluck('v.vehicle_id')->all();
            if ($archive) {
                if ($entity === 'users') {
                    abort_if($id === (int) $actor->user_id, 409, 'You cannot archive your own administrator account.');
                    abort_if($admins->count() <= 1 && $admins->contains('user_id', $id), 409, 'Keep at least one active administrator.');
                }
                abort_if(DB::table('parking_transactions')->whereIn('vehicle_id', $vehicleIds)->where('transaction_status', 'active')->lockForUpdate()->exists(), 409, 'A vehicle is still inside. Record its exit before archiving this record.');
                $this->revokeCredentials($vehicleIds, $entity === 'users' ? $id : null, $reason);
            }
            $values = ['deleted_at' => $archive ? now() : null, 'updated_at' => now()];
            $values[$entity === 'users' ? 'account_status' : 'registration_status'] = $entity === 'users' ? ($archive ? 'deactivated' : 'pending') : ($archive ? 'archived' : 'registered');
            DB::table($entity)->where($key, $id)->update($values);
            if ($entity === 'users') DB::table('personal_access_tokens')->where('tokenable_type', User::class)->where('tokenable_id', $id)->delete();
            if (!$archive && $entity === 'vehicles') {
                $auth = DB::table('vehicle_authorizations')->where('vehicle_id', $id)->orderByDesc('authorization_id')->lockForUpdate()->first();
                $authorization = ['authorization_status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'requested_at' => now(), 'remarks' => 'Restored vehicle requires a fresh authorization review.', 'updated_at' => now()];
                if ($auth) DB::table('vehicle_authorizations')->where('authorization_id', $auth->authorization_id)->update($authorization);
                else DB::table('vehicle_authorizations')->insert($authorization + ['vehicle_id' => $id, 'created_at' => now()]);
            }
            ActivityAudit::record($r, $actor, ($entity === 'users' ? 'account_' : 'vehicle_').($archive ? 'archived' : 'restored'), $entity === 'users' ? User::class : \App\Models\Vehicle::class, $id,
                ['archived' => !$archive], ['archived' => $archive, 'reason' => $reason]);
        }, 3);
        return response()->json(['message' => $archive ? 'Record archived. Its original identifier, documents and transaction history are retained.' : 'Record restored with its original identifier. Fresh approval is required; previous RFID and QR credentials remain revoked.']);
    }

    private function revokeCredentials(array $vehicles, ?int $userId, string $reason): void
    {
        $q = DB::table('rfid_assignments')->where('assignment_status', 'active')->where(fn ($q) => $q->whereIn('vehicle_id', $vehicles)->when($userId, fn ($q) => $q->orWhere('user_id', $userId)));
        $tags = (clone $q)->lockForUpdate()->pluck('rfid_id');
        $q->update(['assignment_status' => 'revoked', 'unassigned_at' => now(), 'updated_at' => now()]);
        DB::table('rfid_tags')->whereIn('rfid_id', $tags)->update(['tag_status' => 'deactivated', 'updated_at' => now()]);
        $visitors = $userId ? DB::table('visitors')->where('user_id', $userId)->pluck('visitor_id')->all() : [];
        DB::table('qr_passes')->where('pass_status', 'active')->where(fn ($q) => $q->whereIn('vehicle_id', $vehicles)->orWhereIn('visitor_id', $visitors)->when($userId, fn ($q) => $q->orWhere('user_id', $userId)))
            ->update(['pass_status' => 'revoked', 'revoked_at' => now(), 'revoke_reason' => mb_substr('Archived record: '.$reason, 0, 250), 'updated_at' => now()]);
        DB::table('visitor_requests')->where('request_status', 'pending')->where(fn ($q) => $q->whereIn('vehicle_id', $vehicles)->orWhereIn('visitor_id', $visitors))
            ->update(['request_status' => 'cancelled', 'review_remarks' => mb_substr('Archived record: '.$reason, 0, 250), 'updated_at' => now()]);
    }
}
