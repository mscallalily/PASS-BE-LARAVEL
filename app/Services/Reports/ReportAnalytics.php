<?php

namespace App\Services\Reports;

use App\Services\Parking\ParkingStats;
use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class ReportAnalytics
{
    public static function snapshot(): array
    {
        $areas = ParkingStats::areas();
        $overall = ParkingStats::overall($areas);
        [$start, $end] = CampusTime::dayRange();
        $q = DB::table('parking_transactions')->where('transaction_status', '<>', 'cancelled');

        return [
            'overall' => $overall, 'inside' => ParkingStats::inside(), 'areas' => array_values($areas),
            'today_entries' => (clone $q)->where('entry_datetime', '>=', $start)->where('entry_datetime', '<', $end)->count(),
            'today_exits' => (clone $q)->whereIn('transaction_status', ['completed', 'force_closed'])->where('exit_datetime', '>=', $start)->where('exit_datetime', '<', $end)->count(),
            'today_denied' => DB::table('access_events')->where('auth_result', 'denied')->where('event_datetime', '>=', $start)->where('event_datetime', '<', $end)->count(),
            'status' => $overall['available'] === 0 ? 'Full' : ($overall['occupancy_percent'] >= 80 ? 'Almost full' : 'Available'),
            'campus_date' => CampusTime::date(),
        ];
    }

    public static function summary(Builder $query, ReportFilters $f): array
    {
        $q = clone $query;
        $totals = $q->selectRaw('COUNT(*) as total, COUNT(DISTINCT r.vehicle_id) as vehicles')->first();
        $metrics = [['label' => 'Matching records', 'value' => (int) $totals->total], ['label' => 'Distinct vehicles', 'value' => (int) $totals->vehicles]];
        if (in_array($f->category(), ['overview', 'entry-exit'], true)) {
            $valid = (clone $query)->where('r.status', '<>', 'cancelled');
            $entries = (clone $valid)->where('r.entry_at', '>=', $f->start())->where('r.entry_at', '<', $f->end())->count();
            $exitsQuery = (clone $valid)->whereIn('r.status', ['completed', 'force_closed'])->where('r.exit_at', '>=', $f->start())->where('r.exit_at', '<', $f->end());
            $metrics = array_merge($metrics, [
                ['label' => 'Entries in period', 'value' => $entries],
                ['label' => 'Exits in period', 'value' => (clone $exitsQuery)->count()],
                ['label' => 'Average daily entries', 'value' => round($entries / max(1, $f->from->diffInDays($f->until)), 2)],
                ['label' => 'Average completed stay', 'value' => ($average = (clone $exitsQuery)->avg('r.duration_minutes')) === null ? null : round((float) $average, 2), 'unit' => 'min'],
                ['label' => 'Still active (matching stays)', 'value' => (clone $valid)->where('r.status', 'active')->count()],
            ]);
        } elseif (in_array($f->category(), ['users', 'audit'], true)) {
            $metrics = [array_merge($metrics[0], ['label' => $f->category() === 'users' ? 'Matching accounts' : 'Recorded actions'])];
        }
        if ($f->category() === 'rfid' && $f->values['dataset'] === 'devices') {
            $metrics = [$metrics[0]];
        }
        if (! in_array($f->category(), ['overview', 'entry-exit'], true)) {
            foreach ((clone $query)->selectRaw('r.status, COUNT(*) as total')->groupBy('r.status')->orderBy('r.status')->limit(8)->get() as $group) {
                $metrics[] = ['label' => ucfirst(str_replace('_', ' ', $group->status ?? 'unknown')), 'value' => (int) $group->total];
            }
        }

        return $metrics;
    }

    private static function bucketExpression(ReportFilters $f): string
    {
        // The storage timezone is UTC; grouping is in the campus timezone (fixed UTC+08:00, no DST).
        return match ($f->values['group_by']) {
            'week' => "DATE_FORMAT(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR), '%x-W%v')",
            'month' => "DATE_FORMAT(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR), '%Y-%m')",
            'year' => "DATE_FORMAT(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR), '%Y')",
            default => 'DATE(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR))',
        };
    }

    public static function charts(Builder $query, ReportFilters $f): array
    {
        $group = self::bucketExpression($f);
        $status = (clone $query)->selectRaw('COALESCE(r.status, \'unknown\') as name, COUNT(*) as value')->groupBy('r.status')->orderBy('name')->get();
        $daily = (clone $query)->selectRaw("$group as label, COUNT(*) as records, SUM(r.vehicle_type='car') as cars, SUM(r.vehicle_type='motorcycle') as motorcycles")
            ->groupByRaw($group)->orderBy('label')->get()->map(fn ($r) => ['label' => (string) $r->label, 'records' => (int) $r->records, 'cars' => (int) $r->cars, 'motorcycles' => (int) $r->motorcycles]);
        $hourly = (clone $query)->selectRaw('HOUR(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR)) as hour, COUNT(*) as value')->groupByRaw('HOUR(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR))')->pluck('value', 'hour');
        $heatmap = (clone $query)->selectRaw('WEEKDAY(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR)) as weekday, HOUR(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR)) as hour, COUNT(*) as value')
            ->groupByRaw('WEEKDAY(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR)), HOUR(DATE_ADD(r.occurred_at, INTERVAL 8 HOUR))')->get();
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[] = ['hour' => sprintf('%02d:00', $i), 'value' => (int) ($hourly[$i] ?? 0)];
        }
        $ranked = collect($hours)->sortByDesc('value')->values();
        $vehicleRows = ['overview', 'entry-exit'];
        if (in_array($f->category(), $vehicleRows, true)) {
            // Activity/peak charts count entries only, avoiding carry-over exits being counted as new entries.
            $entryQuery = (clone $query)->where('r.status', '<>', 'cancelled')->where('r.entry_at', '>=', $f->start())->where('r.entry_at', '<', $f->end());
            $entryFilters = clone $f;
            $entryFilters->values['category'] = 'entry-chart';
            $charts = self::charts($entryQuery, $entryFilters);
            $charts['status'] = $status;

            return $charts;
        }

        return [
            'daily' => $daily, 'status' => $status, 'hourly' => $hours, 'heatmap' => $heatmap,
            'busiest_hour' => ($ranked[0]['value'] ?? 0) > 0 ? $ranked[0]['hour'] : null,
            'least_busy_hour' => ($ranked[0]['value'] ?? 0) > 0 ? $ranked->last()['hour'] : null,
        ];
    }

    /** Historical occupancy reconstructed from stay intervals, clipped to now and the chosen period. */
    public static function occupancy(Builder $query, ReportFilters $f): array
    {
        $start = $f->from->getTimestamp();
        $end = min($f->until->getTimestamp(), now()->getTimestamp());
        $areas = ParkingStats::areas();
        if (isset($f->values['area_id'])) {
            $areas = array_filter($areas, fn ($a) => $a['parking_area_id'] === (int) $f->values['area_id']);
        }
        if (isset($f->values['vehicle_type'])) {
            $areas = array_filter($areas, fn ($a) => $a['area_code'] === ($f->values['vehicle_type'] === 'car' ? 'CAR' : 'MC'));
        }
        $byArea = [];
        foreach ($areas as $area) {
            $byArea[$area['parking_area_id']] = [];
        }
        foreach ((clone $query)->cursor() as $stay) {
            if (! array_key_exists($stay->parking_area_id, $byArea)) {
                continue;
            }
            $in = max($start, CarbonImmutable::parse($stay->entry_at, config('app.timezone'))->getTimestamp());
            $out = min($end, $stay->exit_at ? CarbonImmutable::parse($stay->exit_at, config('app.timezone'))->getTimestamp() : $end);
            if ($out <= $in) {
                continue;
            }
            $byArea[$stay->parking_area_id][] = [$in, $out];
        }
        $rows = [];
        $chart = [];
        $fullSeconds = 0;
        $vehicleSeconds = 0;
        // Use hourly buckets for up to two days, daily thereafter; each bucket has an exact integral and peak.
        $hourly = $f->from->diffInDays($f->until) <= 2;
        for ($at = $f->from; $at->getTimestamp() < $end; $at = $hourly ? $at->addHour() : $at->addDay()) {
            $b0 = $at->getTimestamp();
            $b1 = min(($hourly ? $at->addHour() : $at->addDay())->getTimestamp(), $end);
            $point = ['label' => $at->format($hourly ? 'm-d H:00' : 'Y-m-d'), 'cars' => 0, 'motorcycles' => 0];
            foreach ($areas as $area) {
                $events = [$b0 => 0, $b1 => 0];
                foreach ($byArea[$area['parking_area_id']] as [$in, $out]) {
                    $left = max($in, $b0);
                    $right = min($out, $b1);
                    if ($right <= $left) {
                        continue;
                    }
                    $events[$left] = ($events[$left] ?? 0) + 1;
                    $events[$right] = ($events[$right] ?? 0) - 1;
                }
                ksort($events);
                $current = 0;
                $peak = 0;
                $integral = 0;
                $last = $b0;
                $full = 0;
                foreach ($events as $time => $delta) {
                    $seconds = $time - $last;
                    $integral += $current * $seconds;
                    if ($area['capacity'] > 0 && $current >= $area['capacity']) {
                        $full += $seconds;
                    }
                    $current += $delta;
                    $peak = max($peak, $current);
                    $last = $time;
                }
                $mean = $integral / max(1, $b1 - $b0);
                $vehicleSeconds += $integral;
                $fullSeconds += $full;
                $type = $area['area_code'] === 'MC' ? 'motorcycle' : 'car';
                $rows[] = [
                    'record_id' => $at->format('YmdH').'-'.$area['parking_area_id'], 'occurred_at' => $at->toIso8601String(),
                    'area' => $area['area_name'], 'vehicle_type' => $type, 'status' => 'reconstructed',
                    'peak_vehicles' => $peak, 'average_vehicles' => round($mean, 3), 'reference_capacity' => $area['capacity'],
                    'average_utilization_percent' => $area['capacity'] ? round($mean / $area['capacity'] * 100, 2) : null,
                    'full_capacity_minutes' => round($full / 60, 2),
                    'details' => 'Recorded stays; current capacity reference. Bucket ends '.CarbonImmutable::createFromTimestamp($b1)->setTimezone(CampusTime::zone())->format('Y-m-d H:i:s P'),
                ];
                $point[$type === 'car' ? 'cars' : 'motorcycles'] += round($mean, 3);
            }
            $chart[] = $point;
        }
        if ($f->values['sort'] === 'newest') {
            $rows = array_reverse($rows);
        }
        if ($f->values['sort'] === 'status') {
            usort($rows, fn ($a, $b) => strcmp($a['area'], $b['area']) ?: strcmp($a['occurred_at'], $b['occurred_at']));
        }

        return ['rows' => $rows, 'charts' => ['occupancy' => $chart], 'summary' => [
            ['label' => 'Time buckets × areas', 'value' => count($rows)],
            ['label' => 'Recorded vehicle-hours', 'value' => round($vehicleSeconds / 3600, 2)],
            ['label' => 'Average vehicles in period', 'value' => $end > $start ? round($vehicleSeconds / ($end - $start), 3) : 0],
            ['label' => 'At current capacity reference', 'value' => round($fullSeconds / 60, 2), 'unit' => 'area-min'],
        ]];
    }
}
