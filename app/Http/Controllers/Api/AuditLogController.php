<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AuditActivity;
use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $filters = $request->validate([
            'search' => 'nullable|string|max:100', 'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'outcome' => ['nullable', Rule::in(array_keys(AuditActivity::OUTCOMES))],
            'module' => ['nullable', Rule::in(array_keys(AuditActivity::MODULES))], 'page' => 'nullable|integer|min:1',
        ]);
        $query = DB::table('audit_logs as a')->leftJoin('user_profiles as actor', 'actor.user_id', '=', 'a.user_id');
        if ($search = trim($filters['search'] ?? '')) {
            $readable = preg_replace('/\s+/', '_', $search);
            $query->where(fn ($q) => $q->where('a.actor_label', 'like', "%{$search}%")->orWhere('a.action', 'like', "%{$search}%")
                ->orWhere('a.action', 'like', "%{$readable}%")->orWhere('a.auditable_type', 'like', "%{$search}%")
                ->orWhere('actor.first_name', 'like', "%{$search}%")->orWhere('actor.last_name', 'like', "%{$search}%")
                ->orWhereRaw("TRIM(CONCAT_WS(' ', actor.first_name, actor.last_name)) LIKE ?", ["%{$search}%"]));
        }
        if (!empty($filters['date_from'])) $query->where('a.created_at', '>=', CampusTime::stored(CarbonImmutable::parse($filters['date_from'], CampusTime::zone())->startOfDay()));
        if (!empty($filters['date_to'])) $query->where('a.created_at', '<', CampusTime::stored(CarbonImmutable::parse($filters['date_to'], CampusTime::zone())->startOfDay()->addDay()));
        if (!empty($filters['module'])) {
            $types = (clone $query)->distinct()->pluck('a.auditable_type');
            $matching = $types->filter(fn ($type) => AuditActivity::module($type) === $filters['module'])->values();
            $query->where(fn ($q) => $q->whereIn('a.auditable_type', $matching->filter(fn ($type) => $type !== null)->all())
                ->when($matching->contains(null), fn ($q) => $q->orWhereNull('a.auditable_type')));
        }
        $groups = (clone $query)->select('a.action')->selectRaw('COUNT(*) as aggregate')->groupBy('a.action')->get();
        $summary = array_fill_keys(array_keys(AuditActivity::OUTCOMES), 0);
        foreach ($groups as $group) $summary[AuditActivity::outcome($group->action)] += (int) $group->aggregate;
        if (!empty($filters['outcome'])) $query->whereIn('a.action', $groups->filter(fn ($g) => AuditActivity::outcome($g->action) === $filters['outcome'])->pluck('action')->all());
        $logs = $query->select(['a.log_id', 'a.user_id', 'a.actor_label', 'a.action', 'a.auditable_type', 'a.auditable_id', 'a.old_values', 'a.new_values', 'a.ip_address', 'a.user_agent', 'a.created_at'])
            ->selectRaw("NULLIF(TRIM(CONCAT_WS(' ', actor.first_name, actor.last_name)), '') as actor_name")
            ->orderByDesc('a.created_at')->orderByDesc('a.log_id')->paginate(20)->through(function ($row) {
                foreach (['old_values', 'new_values'] as $field) {
                    $row->$field = AuditActivity::safeValues(is_string($row->$field) ? json_decode($row->$field, true) : $row->$field);
                }
                $row->presentation = AuditActivity::present($row->action, $row->auditable_type, $row->auditable_id, $row->new_values ?? $row->old_values);
                $row->created_at = CarbonImmutable::parse($row->created_at, config('app.timezone'))->toIso8601String();
                return $row;
            });
        return response()->json($logs->toArray() + ['summary' => $summary, 'modules' => AuditActivity::MODULES, 'outcomes' => AuditActivity::OUTCOMES])->header('Cache-Control', 'private, no-store');
    }
}
