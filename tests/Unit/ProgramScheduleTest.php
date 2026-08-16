<?php

namespace Tests\Unit;

use App\Support\ProgramSchedule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ProgramScheduleTest extends TestCase
{
    public function test_single_session_starting_on_a_matching_weekday_expires_same_day(): void
    {
        // 2024-01-01 is a Monday.
        $start = CarbonImmutable::parse('2024-01-01');

        $expiry = ProgramSchedule::calculateExpiryDate($start, ['Monday'], 1);

        $this->assertTrue($expiry->isSameDay(CarbonImmutable::parse('2024-01-01')));
    }

    public function test_start_date_counts_as_session_one_when_it_matches(): void
    {
        // 2024-01-01, 08, 15 are Mondays.
        $start = CarbonImmutable::parse('2024-01-01');

        $expiry = ProgramSchedule::calculateExpiryDate($start, ['Monday'], 3);

        $this->assertTrue($expiry->isSameDay(CarbonImmutable::parse('2024-01-15')));
    }

    public function test_start_date_not_on_a_matching_weekday_begins_counting_from_the_next_match(): void
    {
        // 2024-01-03 is a Wednesday; next Monday is 2024-01-08.
        $start = CarbonImmutable::parse('2024-01-03');

        $expiry = ProgramSchedule::calculateExpiryDate($start, ['Monday'], 1);

        $this->assertTrue($expiry->isSameDay(CarbonImmutable::parse('2024-01-08')));
    }

    public function test_multiple_weekdays_per_week_are_all_counted(): void
    {
        // Mon/Wed/Fri starting Monday 2024-01-01:
        // #1 Mon 01, #2 Wed 03, #3 Fri 05, #4 Mon 08, #5 Wed 10.
        $start = CarbonImmutable::parse('2024-01-01');

        $expiry = ProgramSchedule::calculateExpiryDate($start, ['Monday', 'Wednesday', 'Friday'], 5);

        $this->assertTrue($expiry->isSameDay(CarbonImmutable::parse('2024-01-10')));
    }

    public function test_matching_walks_forward_across_a_week_boundary(): void
    {
        // 2024-01-04 is a Thursday; next Tuesday is 2024-01-09, in the following week.
        $start = CarbonImmutable::parse('2024-01-04');

        $expiry = ProgramSchedule::calculateExpiryDate($start, ['Tuesday'], 1);

        $this->assertTrue($expiry->isSameDay(CarbonImmutable::parse('2024-01-09')));
    }

    public function test_weekday_matching_is_case_insensitive(): void
    {
        $start = CarbonImmutable::parse('2024-01-01');

        $expiry = ProgramSchedule::calculateExpiryDate($start, ['monday'], 1);

        $this->assertTrue($expiry->isSameDay(CarbonImmutable::parse('2024-01-01')));
    }

    public function test_zero_sessions_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProgramSchedule::calculateExpiryDate(CarbonImmutable::parse('2024-01-01'), ['Monday'], 0);
    }

    public function test_empty_weekdays_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProgramSchedule::calculateExpiryDate(CarbonImmutable::parse('2024-01-01'), [], 1);
    }
}
