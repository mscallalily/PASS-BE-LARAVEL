<?php

namespace Tests\Unit;

use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class CampusTimeTest extends TestCase
{
    public function test_school_calendar_is_philippine_even_with_a_stale_display_override(): void
    {
        config(['app.display_timezone' => 'UTC', 'app.timezone' => 'UTC']);
        $at = CarbonImmutable::parse('2026-10-21T16:30:00Z');
        $this->assertSame('Asia/Manila', CampusTime::zone());
        $this->assertSame('2026-10-22', CampusTime::date($at));
        $this->assertSame(['2026-10-21 16:00:00', '2026-10-22 16:00:00'], CampusTime::dayRange($at));
        $this->assertSame('UTC', config('app.timezone'));
    }

    public function test_serializing_raw_or_offset_times_preserves_the_same_moment(): void
    {
        foreach (['2026-10-22 00:22:00', '2026-10-22T00:22:00Z', '2026-10-22T08:22:00+08:00', CarbonImmutable::parse('2026-10-22T08:22:00+08:00')] as $value) {
            $this->assertSame('2026-10-22T00:22:00+00:00', CampusTime::apiTimestamp($value));
        }
        $this->assertNull(CampusTime::apiTimestamp(null));
        $this->assertNull(CampusTime::apiTimestamp(''));
    }
}
