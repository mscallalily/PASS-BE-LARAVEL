<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Times are stored in UTC (config app.timezone); the campus works in
 * Asia/Manila. Use these helpers for calendar
 * rules so "today" means the campus day, not the UTC day.
 */
class CampusTime
{
    public const TIMEZONE = 'Asia/Manila';

    public static function zone(): string
    {
        return self::TIMEZONE;
    }

    /** Current time on campus. */
    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::zone());
    }

    /** Campus calendar date (Y-m-d) of a moment, default now. */
    public static function date(?CarbonImmutable $at = null): string
    {
        return ($at ?? CarbonImmutable::now())->setTimezone(self::zone())->toDateString();
    }

    /**
     * Start and end of the campus day containing $at, as UTC datetime
     * strings for comparing with stored columns.
     *
     * @return array{0: string, 1: string}
     */
    public static function dayRange(?CarbonImmutable $at = null): array
    {
        $start = ($at ?? CarbonImmutable::now())->setTimezone(self::zone())->startOfDay();

        return [self::stored($start), self::stored($start->addDay())];
    }

    /** Human-readable campus time for messages, e.g. "Oct 15, 2026 1:02 PM". */
    public static function label(CarbonImmutable $at): string
    {
        return $at->setTimezone(self::zone())->format('M j, Y g:i A');
    }

    /** A moment as a stored (UTC, app timezone) datetime string. */
    public static function stored(CarbonImmutable $at): string
    {
        return $at->setTimezone(config('app.timezone'))->toDateTimeString();
    }

    /** Give raw database timestamps an explicit UTC offset for every client. */
    public static function apiTimestamp(string|\DateTimeInterface|null $value): ?string
    {
        return $value ? CarbonImmutable::parse($value, 'UTC')->utc()->toIso8601String() : null;
    }
}
