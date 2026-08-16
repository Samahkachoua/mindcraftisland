<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class ProgramSchedule
{
    /**
     * Compute the date of a program enrollment's final (Nth) session.
     *
     * Starting at $startDate, walks forward day by day counting each date whose
     * weekday name appears in $weekdays (case-insensitive) — including $startDate
     * itself if it matches — until $numSessions matching dates have been counted.
     * Returns the date of that final matching session.
     *
     * @param array<int, string> $weekdays e.g. ['Monday', 'Wednesday']
     */
    public static function calculateExpiryDate(\DateTimeInterface $startDate, array $weekdays, int $numSessions): CarbonImmutable
    {
        if ($numSessions < 1) {
            throw new \InvalidArgumentException('numSessions must be at least 1.');
        }
        if (empty($weekdays)) {
            throw new \InvalidArgumentException('weekdays must not be empty.');
        }

        $matchDays = array_map('strtolower', $weekdays);
        $cursor = CarbonImmutable::instance($startDate)->startOfDay();
        $counted = 0;

        while (true) {
            if (in_array(strtolower($cursor->format('l')), $matchDays, true)) {
                $counted++;
                if ($counted === $numSessions) {
                    return $cursor;
                }
            }
            $cursor = $cursor->addDay();
        }
    }
}
