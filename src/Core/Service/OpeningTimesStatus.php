<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use DateTimeImmutable;
use DateTimeZone;

class OpeningTimesStatus
{
    private const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function isOpenNow(?array $openingTimes, ?string $timezone, ?DateTimeImmutable $now = null): ?bool
    {
        if ($openingTimes === null || $timezone === null) {
            return null;
        }

        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            return null;
        }

        $local = ($now ?? new DateTimeImmutable())->setTimezone(new DateTimeZone($timezone));
        $minutes = ((int)$local->format('G')) * 60 + (int)$local->format('i');
        $todayIndex = ((int)$local->format('N')) - 1;
        $yesterdayIndex = ($todayIndex + 6) % 7;

        foreach ($this->intervalsFor($openingTimes, self::DAY_KEYS[$todayIndex]) as [$start, $end]) {
            if ($minutes >= $start && $minutes < $end) {
                return true;
            }
        }

        foreach ($this->intervalsFor($openingTimes, self::DAY_KEYS[$yesterdayIndex]) as [$start, $end]) {
            if ($end > 24 * 60 && $minutes < $end - 24 * 60) {
                return true;
            }
        }

        return false;
    }

    private function intervalsFor(array $openingTimes, string $dayKey): array
    {
        $day = $openingTimes[$dayKey] ?? null;
        if (!is_array($day) || ($day['closed'] ?? true) === true) {
            return [];
        }

        $intervals = [];
        foreach ($day['ranges'] ?? [] as $range) {
            if (!is_array($range) || count($range) !== 2) {
                continue;
            }

            $start = $this->toMinutes($range[0]);
            $end = $this->toMinutes($range[1]);
            if ($end <= $start) {
                $end += 24 * 60;
            }

            $intervals[] = [$start, $end];
        }

        return $intervals;
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return ((int)$hours) * 60 + (int)$minutes;
    }
}
