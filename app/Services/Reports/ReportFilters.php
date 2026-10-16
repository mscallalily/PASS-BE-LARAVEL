<?php

namespace App\Services\Reports;

use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ReportFilters
{
    public const TITLES = [
        'overview' => 'Parking Operations Overview',
        'entry-exit' => 'Vehicle Entry & Exit',
        'occupancy' => 'Parking Occupancy',
        'rfid' => 'RFID Transactions & Devices',
        'users' => 'User & Account Records',
        'vehicles' => 'Vehicle & Driver Records',
        'visitors' => 'Visitor & Temporary QR Access',
        'security' => 'Security & Access Incidents',
        'audit' => 'System Activity & Audit',
    ];

    public array $values;

    public CarbonImmutable $from;

    public CarbonImmutable $until;

    public function __construct(Request $request)
    {
        $data = $request->validate([
            'category' => ['nullable', Rule::in(array_keys(self::TITLES))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'vehicle_type' => ['nullable', Rule::in(['car', 'motorcycle'])],
            'user_type' => ['nullable', Rule::in(['student', 'faculty', 'employee', 'security', 'admin', 'guest', 'other'])],
            'status' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:100'],
            'area_id' => ['nullable', 'integer', 'exists:parking_areas,parking_area_id'],
            'method' => ['nullable', Rule::in(['rfid', 'qr', 'manual'])],
            'dataset' => ['nullable', Rule::in(['scans', 'assignments', 'devices'])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'duration', 'status'])],
            'group_by' => ['nullable', Rule::in(['day', 'week', 'month', 'year'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'format' => ['nullable', Rule::in(['csv', 'excel', 'print'])],
        ]);
        $this->from = CarbonImmutable::parse($data['date_from'] ?? CampusTime::date(), CampusTime::zone())->startOfDay();
        $this->until = CarbonImmutable::parse($data['date_to'] ?? CampusTime::date(), CampusTime::zone())->startOfDay()->addDay();
        if ($this->until <= $this->from || $this->from->diffInDays($this->until) > 366) {
            throw ValidationException::withMessages(['date_to' => 'Choose a period of 1 to 366 days.']);
        }
        $this->values = array_merge([
            'category' => 'overview', 'dataset' => 'scans', 'sort' => 'newest', 'group_by' => 'day',
            'per_page' => 25, 'page' => 1,
        ], array_filter($data, fn ($v) => $v !== null && $v !== ''));
        $this->values['date_from'] = $this->from->toDateString();
        $this->values['date_to'] = $this->until->subDay()->toDateString();
        $allowed = ReportDataset::filterKeys($this->category(), $this->values['dataset']);
        foreach (['vehicle_type', 'user_type', 'status', 'area_id', 'method', 'search'] as $key) {
            if (! in_array($key, $allowed, true) && isset($this->values[$key])) {
                throw ValidationException::withMessages([$key => 'This filter does not apply to the selected report.']);
            }
        }
        if (isset($this->values['status']) && ! in_array($this->values['status'], ReportDataset::statuses($this->category(), $this->values['dataset']), true)) {
            throw ValidationException::withMessages(['status' => 'Choose a status supported by this report.']);
        }
    }

    public function category(): string
    {
        return $this->values['category'];
    }

    public function start(): string
    {
        return CampusTime::stored($this->from);
    }

    public function end(): string
    {
        return CampusTime::stored($this->until);
    }

    public function title(): string
    {
        return self::TITLES[$this->category()];
    }
}
