<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Forecast;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StockCountLine;
use Citadel\Aureum\Core\Entity\StockMovement;
use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\StockCountLineRepository;
use Citadel\Aureum\Core\Repository\StockMovementRepository;
use DateTimeImmutable;

class InventoryForecastService
{
    public const WINDOW_DAYS = 56;

    public function __construct(
        private readonly InventoryItemRepository $itemRepository,
        private readonly StockCountLineRepository $countLineRepository,
        private readonly StockMovementRepository $movementRepository,
        private readonly ConsumptionCalculator $consumptionCalculator,
        private readonly ForecastCalculator $forecastCalculator,
    ) {
    }

    /**
     * @return array<ItemForecast>
     */
    public function forecastForHotel(Hotel $hotel): array
    {
        $now = new DateTimeImmutable();
        $since = $now->modify('-' . self::WINDOW_DAYS . ' days');

        $countsByItem = $this->groupCounts($this->countLineRepository->findForHotelSince($hotel, $since));
        $movementsByItem = $this->groupMovements($this->movementRepository->findForHotelSince($hotel, $since));

        $forecasts = [];
        foreach ($this->itemRepository->findActiveByHotel($hotel) as $item) {
            $id = $item->getId();
            $forecasts[] = $this->build(
                $item,
                $countsByItem[$id] ?? [],
                $movementsByItem[$id] ?? [],
                $now,
            );
        }

        usort($forecasts, static function (ItemForecast $a, ItemForecast $b): int {
            $weight = $a->status->sortWeight() <=> $b->status->sortWeight();
            if ($weight !== 0) {
                return $weight;
            }

            return ($a->orderBy?->getTimestamp() ?? PHP_INT_MAX)
                <=> ($b->orderBy?->getTimestamp() ?? PHP_INT_MAX);
        });

        return $forecasts;
    }

    public function forecastForItem(InventoryItem $item): ItemForecast
    {
        $now = new DateTimeImmutable();
        $since = $now->modify('-' . self::WINDOW_DAYS . ' days');

        return $this->build(
            $item,
            $this->toCountObservations($this->countLineRepository->findForItemSince($item, $since)),
            $this->toMovementObservations($this->movementRepository->findForItemSince($item, $since)),
            $now,
        );
    }

    public function stockOnHand(InventoryItem $item): int
    {
        $now = new DateTimeImmutable();
        $since = $now->modify('-' . self::WINDOW_DAYS . ' days');

        return self::stockOnHandFrom(
            $this->toCountObservations($this->countLineRepository->findForItemSince($item, $since)),
            $this->toMovementObservations($this->movementRepository->findForItemSince($item, $since)),
        );
    }

    /**
     * @param array<CountObservation> $counts
     * @param array<MovementObservation> $movements
     */
    public static function stockOnHandFrom(array $counts, array $movements): int
    {
        if ($counts === []) {
            return 0;
        }

        usort($counts, static fn (CountObservation $a, CountObservation $b): int
            => $a->at <=> $b->at);

        $latest = $counts[count($counts) - 1];
        $stock = $latest->quantity;

        foreach ($movements as $movement) {
            if ($movement->at > $latest->at) {
                $stock += $movement->signedQuantity;
            }
        }

        return $stock;
    }

    /**
     * @param array<CountObservation> $counts
     * @param array<MovementObservation> $movements
     */
    private function build(
        InventoryItem $item,
        array $counts,
        array $movements,
        DateTimeImmutable $now,
    ): ItemForecast {
        return $this->forecastCalculator->forecast(
            $item,
            self::stockOnHandFrom($counts, $movements),
            $this->consumptionCalculator->intervals($counts, $movements),
            $now,
        );
    }

    /**
     * @param array<StockCountLine> $lines
     * @return array<int, array<CountObservation>>
     */
    private function groupCounts(array $lines): array
    {
        $grouped = [];
        foreach ($lines as $line) {
            $grouped[$line->getItem()->getId()][] = $this->toCountObservation($line);
        }

        return $grouped;
    }

    /**
     * @param array<StockMovement> $movements
     * @return array<int, array<MovementObservation>>
     */
    private function groupMovements(array $movements): array
    {
        $grouped = [];
        foreach ($movements as $movement) {
            $grouped[$movement->getItem()->getId()][] = $this->toMovementObservation($movement);
        }

        return $grouped;
    }

    /**
     * @param array<StockCountLine> $lines
     * @return array<CountObservation>
     */
    private function toCountObservations(array $lines): array
    {
        return array_map($this->toCountObservation(...), $lines);
    }

    /**
     * @param array<StockMovement> $movements
     * @return array<MovementObservation>
     */
    private function toMovementObservations(array $movements): array
    {
        return array_map($this->toMovementObservation(...), $movements);
    }

    private function toCountObservation(StockCountLine $line): CountObservation
    {
        return new CountObservation(
            DateTimeImmutable::createFromInterface($line->getStockCount()->getCountedAt()),
            $line->getQuantity(),
        );
    }

    private function toMovementObservation(StockMovement $movement): MovementObservation
    {
        return new MovementObservation(
            DateTimeImmutable::createFromInterface($movement->getOccurredAt()),
            $movement->getSignedQuantity(),
        );
    }
}
