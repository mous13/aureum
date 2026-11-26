<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

final class StreetDistance
{
    private const EARTH_RADIUS_METRES = 6371000;
    private const MAX_SAMPLES = 200;

    /**
     * @param list<list<array{float, float}>> $a
     * @param list<list<array{float, float}>> $b
     */
    public static function metres(array $a, array $b): int
    {
        $best = INF;
        foreach (self::sample($a) as $p) {
            foreach (self::sample($b) as $q) {
                $best = min($best, self::haversine($p, $q));
            }
        }

        return is_finite($best) ? (int)round($best) : 0;
    }

    /**
     * @param list<list<array{float, float}>> $lines
     * @return list<array{float, float}>
     */
    private static function sample(array $lines): array
    {
        $points = array_merge(...$lines);
        $count = count($points);
        if ($count <= self::MAX_SAMPLES) {
            return $points;
        }

        $step = $count / self::MAX_SAMPLES;
        $sampled = [];
        for ($i = 0; $i < self::MAX_SAMPLES; $i++) {
            $sampled[] = $points[(int)floor($i * $step)];
        }

        return $sampled;
    }

    /**
     * @param array{float, float} $p
     * @param array{float, float} $q
     */
    private static function haversine(array $p, array $q): float
    {
        [$lng1, $lat1] = array_map('deg2rad', $p);
        [$lng2, $lat2] = array_map('deg2rad', $q);
        $h = sin(($lat2 - $lat1) / 2) ** 2 + cos($lat1) * cos($lat2) * sin(($lng2 - $lng1) / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METRES * asin(sqrt($h));
    }
}
