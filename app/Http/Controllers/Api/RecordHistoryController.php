<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportDataset;
use App\Support\RecordHistoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class RecordHistoryController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->isAdmin(), 403);
        $data = $r->validate(['record_type' => ['nullable', Rule::in(array_keys(RecordHistoryCatalog::ENTITIES))], 'record_id' => ['nullable', 'string', 'max:128'], 'revision_id' => ['nullable', 'integer', 'min:1'], 'operation' => ['nullable', Rule::in(['baseline', 'created', 'updated', 'archived', 'restored', 'deleted'])], 'page' => ['nullable', 'integer', 'min:1']]);
        $q = DB::table('record_history');
        foreach (['record_type', 'record_id', 'revision_id', 'operation'] as $key) if (!empty($data[$key])) $q->where($key, $data[$key]);
        $rows = $q->orderByDesc('revision_id')->paginate(20);
        $rows->through(function ($row) {
            $documents = [];
            foreach (['before', 'after'] as $side) {
                $key = $side.'_values'; $values = json_decode($row->$key ?? 'null', true);
                foreach (RecordHistoryCatalog::DOCUMENTS[$row->record_type] ?? [] as $field) if (!empty($values[$field])) $documents[] = ['side' => $side, 'field' => $field];
                $row->$key = $values === null ? null : ReportDataset::redact($values);
            }
            $row->documents = $documents;
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
