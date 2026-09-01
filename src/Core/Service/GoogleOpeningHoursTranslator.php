<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

class GoogleOpeningHoursTranslator
{
    private const DAY_KEYS = [0 => 'sun', 1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat'];
    private const WEEK_ORDER = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function translate(?array $regularOpeningHours): ?array
    {
        $periods = $regularOpeningHours['periods'] ?? [];
        if ($periods === []) {
            return null;
        }

        $week = [];
        foreach (self::WEEK_ORDER as $day) {
            $week[$day] = ['closed' => true, 'ranges' => []];
        }

        foreach ($periods as $period) {
            $open = $period['open'] ?? null;
            if (!is_array($open) || !isset($open['day'])) {
                continue;
            }

            if (!isset($period['close'])) {
                foreach (self::WEEK_ORDER as $day) {
                    $week[$day] = ['closed' => false, 'ranges' => [['00:00', '00:00']]];
                }

                return $week;
            }

            $dayKey = self::DAY_KEYS[(int)$open['day']] ?? null;
            if ($dayKey === null) {
                continue;
            }

            $week[$dayKey]['closed'] = false;
            $week[$dayKey]['ranges'][] = [
                $this->formatTime($open),
                $this->formatTime($period['close']),
            ];
        }

        foreach ($week as $day => $schedule) {
            usort(
                $week[$day]['ranges'],
                static fn(array $a, array $b) => strcmp($a[0], $b[0]),
            );
        }

        return $week;
    }

    private function formatTime(array $point): string
    {
        return sprintf('%02d:%02d', (int)($point['hour'] ?? 0), (int)($point['minute'] ?? 0));
    }
}
