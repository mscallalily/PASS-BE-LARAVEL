<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportAnalytics;
use App\Services\Reports\ReportDataset;
use App\Services\Reports\ReportExport;
use App\Services\Reports\ReportFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdminReportController extends Controller
{
    public static function columns(string $category, string $dataset): array
    {
        if ($category === 'occupancy') {
            return [
                'occurred_at' => 'Bucket start (Manila)', 'area' => 'Parking area', 'vehicle_type' => 'Vehicle type',
                'peak_vehicles' => 'Peak vehicles', 'average_vehicles' => 'Average vehicles', 'reference_capacity' => 'Current reference capacity',
                'average_utilization_percent' => 'Average utilization (%)', 'full_capacity_minutes' => 'Minutes at reference capacity',
            ];
        }
        $columns = ['record_id' => 'Record ID', 'occurred_at' => 'Recorded at (Manila)'];
        if ($category !== 'audit' && $category !== 'users' && ! ($category === 'rfid' && $dataset === 'devices')) {
            $columns += ['plate_number' => 'Plate', 'vehicle_type' => 'Vehicle type'];
        }
        $columns += ['person' => $category === 'rfid' && $dataset === 'devices' ? 'Device' : 'Account / visitor', 'user_code' => 'Account ID', 'user_type' => 'User type', 'status' => 'Status'];
        if (in_array($category, ['overview', 'entry-exit', 'visitors'], true)) {
            $columns += ['entry_at' => 'Actual entry (Manila)', 'exit_at' => 'Actual exit (Manila)'];
        }
        if (in_array($category, ['overview', 'entry-exit'], true)) {
            $columns += ['duration_minutes' => 'Completed duration (min)', 'method' => 'Access method', 'area' => 'Parking area'];
        }
        if ($category === 'visitors') {
            $columns += ['credential' => 'QR pass reference'];
        }
        if ($category === 'rfid' || $category === 'security') {
            $columns += ['credential' => 'Credential reference', 'direction' => 'Direction', 'area' => 'Gate / area'];
        }
        if ($category === 'audit') {
            $columns += ['method' => 'Action', 'old_values' => 'Before', 'new_values' => 'After'];
        }

        return $columns + ['details' => 'Details'];
    }

    private function metadata(Request $request, ReportFilters $f, int $total): array
    {
        $basis = match ($f->category()) {
            'overview','entry-exit' => 'Transactions with entry OR exit in the selected Manila period. Charts count valid entries in period; average duration uses completed exits in period.',
            'occupancy' => 'Stay intervals overlapping the selected period, clipped to now. Historical vehicle counts are reconstructed; current capacity is only a reference, not a historical capacity snapshot.',
            'visitors' => 'Scheduled visit start in the selected Manila period; actual entry/exit are shown separately.',
            'users' => 'Accounts registered in the selected period; status and requirement indicators are current values.',
            'vehicles' => 'Authorization requested (or vehicle registered without an authorization) in period. Status, primary driver and latest license are current values; decision changes appear in Audit.',
            'rfid' => $f->values['dataset'] === 'devices' ? 'Current configured readers and gates. Date range does not filter inventory. Status/heartbeat are stored values, not a live connectivity test.' : ($f->values['dataset'] === 'assignments' ? 'Assignments issued in the selected period, including retained revoked/replaced assignment records.' : 'RFID scans recorded in the selected period. A denied scan is not necessarily a hardware failure.'),
            default => 'Recorded events/actions in the selected Manila period. Unrecorded historical changes cannot be reconstructed.',
        };

        return [
            'title' => $f->title(), 'category' => $f->category(), 'dataset' => $f->values['dataset'], 'filters' => $f->values,
            'generated_at' => now()->toIso8601String(), 'generated_by' => $request->user()->user_code,
            'timezone' => 'Asia/Manila', 'total_records' => $total, 'date_basis' => $basis,
            'columns' => self::columns($f->category(), $f->values['dataset']),
            'filter_keys' => ReportDataset::filterKeys($f->category(), $f->values['dataset']),
            'statuses' => ReportDataset::statuses($f->category(), $f->values['dataset']),
            'notes' => [
                'Occupancy is based on recorded entries/exits and active slot assignments; physical occupancy is not confirmed by sensors.',
                'No device disconnection/error history is stored. Reader status is configuration plus last stored heartbeat.',
                'An active stay without an exit is still inside, not automatically a missing-exit incident. Only structural conflicts are flagged for review.',
                'Account rejection uses the existing deactivated status. Historical decisions are shown only when recorded in Audit.',
                'RFID/QR scan rows show credential references; QR secrets, full license numbers and uploaded documents are excluded.',
            ],
        ];
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $f = new ReportFilters($request);
        $query = ReportDataset::query($f);
        $page = (int) $f->values['page'];
        $per = (int) $f->values['per_page'];
        if ($f->category() === 'occupancy') {
            $report = ReportAnalytics::occupancy($query, $f);
            $total = count($report['rows']);
            $data = array_slice($report['rows'], ($page - 1) * $per, $per);
            $summary = $report['summary'];
            $charts = $report['charts'];
        } else {
            $total = (clone $query)->count();
            $data = ReportDataset::ordered(clone $query, $f)->offset(($page - 1) * $per)->limit($per)->get()->map(fn ($r) => ReportDataset::row($r));
            $summary = ReportAnalytics::summary($query, $f);
            $charts = ReportAnalytics::charts($query, $f);
        }

        return response()->json([
            'data' => $data, 'summary' => $summary, 'charts' => $charts,
            'meta' => $this->metadata($request, $f, $total),
            'pagination' => ['current_page' => $page, 'last_page' => max(1, (int) ceil($total / $per)), 'per_page' => $per, 'total' => $total],
            'snapshot' => ReportAnalytics::snapshot(),
            'areas' => DB::table('parking_areas')->where('is_active', true)->orderBy('area_code')->get(['parking_area_id', 'area_name']),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $f = new ReportFilters($request);
        $query = ReportDataset::query($f);
        if ($f->category() === 'occupancy') {
            $report = ReportAnalytics::occupancy($query, $f);
            $total = count($report['rows']);
            $rows = fn () => collect($report['rows']);
            $summary = $report['summary'];
        } else {
            $total = (clone $query)->count();
            $rows = fn () => ReportDataset::ordered(clone $query, $f)->cursor()->map(fn ($r) => ReportDataset::row($r));
            $summary = ReportAnalytics::summary($query, $f);
        }
        // Fail explicitly rather than silently exporting a truncated report.
        abort_if($total > 50000, 422, 'More than 50,000 matching rows. Narrow the period or filters before exporting.');
        $meta = $this->metadata($request, $f, $total);

        return ReportExport::download($f->values['format'] ?? 'csv', $meta, $summary, $rows);
    }
}
