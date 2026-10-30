<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\RecordHistoryCatalog;
use App\Support\AuditActivity;
use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class RecordHistoryController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->isAdmin(), 403);
        $data = $r->validate([
            'record_type' => ['nullable', Rule::in(array_keys(RecordHistoryCatalog::ENTITIES))],
            'record_id' => ['nullable', 'string', 'max:128'], 'revision_id' => ['nullable', 'integer', 'min:1'],
            'operation' => ['nullable', Rule::in(['baseline', 'created', 'updated', 'archived', 'restored', 'deleted'])],
            'search' => ['nullable', 'string', 'max:100'], 'scope' => ['nullable', Rule::in(['business', 'all'])],
            'include_baseline' => ['nullable', 'boolean'], 'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($r->filled('date_from') ? ['after_or_equal:date_from'] : [])], 'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $q = DB::table('record_history');
        foreach (['record_type', 'record_id', 'revision_id', 'operation'] as $key) if (!empty($data[$key])) $q->where($key, $data[$key]);
        if (empty($data['revision_id'])) {
            if (($data['scope'] ?? 'all') === 'business') $q->whereNotIn('record_type', ['login_attempts', 'audit_logs', 'audit_alert_reviews']);
            if (array_key_exists('include_baseline', $data) && !$data['include_baseline'] && ($data['operation'] ?? null) !== 'baseline') $q->where('operation', '!=', 'baseline');
        }
        if ($search = trim($data['search'] ?? '')) {
            $q->where(function ($query) use ($search) {
                $query->where('record_id', 'like', "%{$search}%")->orWhere('actor_label', 'like', "%{$search}%");
                foreach (['before_values', 'after_values'] as $side) {
                    foreach (['plate_number', 'user_code', 'first_name', 'last_name', 'email', 'slot_code', 'area_name', 'zone_name', 'reader_code', 'actor_label'] as $field) {
                        $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT($side, '$.$field')) LIKE ?", ["%{$search}%"]);
                    }
                }
            });
        }
        if (!empty($data['date_from'])) $q->where('recorded_at', '>=', CampusTime::stored(CarbonImmutable::parse($data['date_from'], CampusTime::zone())->startOfDay()));
        if (!empty($data['date_to'])) $q->where('recorded_at', '<', CampusTime::stored(CarbonImmutable::parse($data['date_to'], CampusTime::zone())->startOfDay()->addDay()));
        $rows = $q->orderByDesc('revision_id')->paginate(20);
        $rows->through(function ($row) {
            $documents = [];
            foreach (['before', 'after'] as $side) {
                $key = $side.'_values'; $values = json_decode($row->$key ?? 'null', true);
                foreach (RecordHistoryCatalog::DOCUMENTS[$row->record_type] ?? [] as $field) if (!empty($values[$field])) $documents[] = ['side' => $side, 'field' => $field];
                $row->$key = $values === null ? null : AuditActivity::safeValues($values);
            }
            $row->documents = $documents;
            $row->record_type_label = RecordHistoryCatalog::ENTITIES[$row->record_type] ?? AuditActivity::label($row->record_type);
            $row->record_label = AuditActivity::recordLabel($row->record_type, $row->record_id, $row->after_values ?? $row->before_values);
            $row->recorded_at = \Carbon\Carbon::parse($row->recorded_at, config('app.timezone'))->toIso8601String();
            return $row;
        });
        return response()->json(['history' => $rows, 'entities' => RecordHistoryCatalog::ENTITIES, 'started_at' => DB::table('record_history')->min('recorded_at')])->header('Cache-Control', 'private, no-store');
    }

    public function document(Request $r, int $id, string $field)
    {
        abort_unless($r->user()->isAdmin(), 403);
        $side = $r->validate(['side' => ['required', Rule::in(['before', 'after'])]])['side'];
        $row = DB::table('record_history')->where('revision_id', $id)->first(); abort_unless($row, 404);
        abort_unless(in_array($field, RecordHistoryCatalog::DOCUMENTS[$row->record_type] ?? [], true), 404);
        $path = json_decode($row->{$side.'_values'} ?? '{}', true)[$field] ?? null;
        abort_unless(is_string($path) && $path !== '' && !str_contains($path, '..') && !preg_match('~^[\\\\/]|:~', $path), 404);
        abort_unless(Storage::disk('private')->exists($path), 404, 'This earlier document is no longer available. Files removed before retention was installed cannot be recovered.');
        return Storage::disk('private')->response($path, 'historical-document-'.$id.'.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }
}
