<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Forecast;

class ConsumptionCalculator
{
    private const SECONDS_PER_DAY = 86400;

    /**
     * @param array<CountObservation> $counts
     * @param array<MovementObservation> $movements
     * @return array<ConsumptionInterval>
     */
    public function intervals(array $counts, array $movements): array
    {
        usort($counts, static fn (CountObservation $a, CountObservation $b): int
            => $a->at <=> $b->at);

        $intervals = [];
        $count = count($counts);

        for ($i = 1; $i < $count; $i++) {
            $previous = $counts[$i - 1];
            $current = $counts[$i];

            $seconds = $current->at->getTimestamp() - $previous->at->getTimestamp();
            if ($seconds <= 0) {
                continue;
            }

            $days = $seconds / self::SECONDS_PER_DAY;
            $deliveries = $this->deliveriesBetween($movements, $previous, $current);
            $consumption = $previous->quantity + $deliveries - $current->quantity;

            $intervals[] = new ConsumptionInterval(
                $previous->at,
                $current->at,
                $consumption,
                $days,
                $consumption < 0,
            );
        }

        return $intervals;
    }

    /**
     * @param array<MovementObservation> $movements
     */
    private function deliveriesBetween(
        array $movements,
        CountObservation $previous,
        CountObservation $current,
    ): int {
        $total = 0;

        foreach ($movements as $movement) {
            if ($movement->signedQuantity <= 0) {
                continue;
            }

            if ($movement->at > $previous->at && $movement->at <= $current->at) {
                $total += $movement->signedQuantity;
            }
        }

        return $total;
    }
}
