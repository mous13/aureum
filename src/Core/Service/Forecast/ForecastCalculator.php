<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Forecast;

use Citadel\Aureum\Core\Entity\Enum\ReorderStatus;
use Citadel\Aureum\Core\Entity\InventoryItem;
use DateTimeImmutable;

class ForecastCalculator
{
    public const ORDER_HORIZON_DAYS = 28;
    public const MINIMUM_INTERVALS = 4;
    public const MINIMUM_OBSERVED_DAYS = 21.0;
    private const ORDER_NOW_DAYS = 7;
    private const DUE_SOON_DAYS = 28;

    /**
     * @param array<ConsumptionInterval> $intervals
     */
    public function forecast(
        InventoryItem $item,
        int $stockOnHand,
        array $intervals,
        DateTimeImmutable $now,
    ): ItemForecast {
        $needsReview = false;
        $consumption = 0;
        $observedDays = 0.0;
        $usable = 0;

        foreach ($intervals as $interval) {
            if ($interval->excluded) {
                $needsReview = true;
                continue;
            }

            $consumption += $interval->consumption;
            $observedDays += $interval->days;
            $usable++;
        }

        $rate = $observedDays > 0.0 ? $consumption / $observedDays : null;
        $provisional = $usable < self::MINIMUM_INTERVALS || $observedDays < self::MINIMUM_OBSERVED_DAYS;

        if ($item->getLeadTimeDays() === null) {
            return $this->barren($item, $stockOnHand, $rate, ReorderStatus::NEEDS_SETUP, $needsReview, $provisional, $usable, $observedDays);
        }

        if ($rate === null) {
            return $this->barren($item, $stockOnHand, null, ReorderStatus::NO_DATA, $needsReview, $provisional, $usable, $observedDays);
        }

        if ($rate < 0.0) {
            return $this->barren($item, $stockOnHand, $rate, ReorderStatus::NO_DATA, true, $provisional, $usable, $observedDays);
        }

        if ($rate <= 0.0) {
            return $this->barren($item, $stockOnHand, 0.0, ReorderStatus::OK, $needsReview, $provisional, $usable, $observedDays);
        }

        $leadTime = $item->getLeadTimeDays();
        $buffer = $item->getSafetyBufferDays();
        $daysOfCover = $stockOnHand / $rate;
        $daysUntilOrder = $daysOfCover - $leadTime - $buffer;
        $orderBy = $now->modify(sprintf('%+d days', (int)floor($daysUntilOrder)));

        $required = (int)ceil($rate * ($leadTime + $buffer + self::ORDER_HORIZON_DAYS)) - $stockOnHand;
        $orderQuantity = null;
        $orderPacks = null;

        if ($required > 0) {
            $orderPacks = $item->packsFor($required);
            $packSize = $item->getPackSize();
            $orderQuantity = $packSize !== null && $packSize > 0
                ? $orderPacks * $packSize
                : $required;
        }

        return new ItemForecast(
            $item,
            $stockOnHand,
            $rate,
            $daysOfCover,
            $orderBy,
            $orderQuantity,
            $orderPacks,
            $this->statusFor($daysUntilOrder),
            $needsReview,
            $provisional,
            $usable,
            $observedDays,
        );
    }

    private function statusFor(float $daysUntilOrder): ReorderStatus
    {
        if ($daysUntilOrder < 0) {
            return ReorderStatus::OVERDUE;
        }

        if ($daysUntilOrder <= self::ORDER_NOW_DAYS) {
            return ReorderStatus::ORDER_NOW;
        }

        if ($daysUntilOrder <= self::DUE_SOON_DAYS) {
            return ReorderStatus::DUE_SOON;
        }

        return ReorderStatus::OK;
    }

    private function barren(
        InventoryItem $item,
        int $stockOnHand,
        ?float $rate,
        ReorderStatus $status,
        bool $needsReview,
        bool $provisional,
        int $usable,
        float $observedDays,
    ): ItemForecast {
        return new ItemForecast(
            $item,
            $stockOnHand,
            $rate,
            null,
            null,
            null,
            null,
            $status,
            $needsReview,
            $provisional,
            $usable,
            $observedDays,
        );
    }
}
