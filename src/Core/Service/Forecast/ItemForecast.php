<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Forecast;

use Citadel\Aureum\Core\Entity\Enum\ReorderStatus;
use Citadel\Aureum\Core\Entity\InventoryItem;
use DateTimeImmutable;

final readonly class ItemForecast
{
    public function __construct(
        public InventoryItem $item,
        public int $stockOnHand,
        public ?float $ratePerDay,
        public ?float $daysOfCover,
        public ?DateTimeImmutable $orderBy,
        public ?int $orderQuantity,
        public ?int $orderPacks,
        public ReorderStatus $status,
        public bool $needsReview,
        public bool $provisional,
        public int $usableIntervals,
        public float $observedDays,
    ) {
    }

    public function orderByWeek(): ?string
    {
        return $this->orderBy?->format('o-\WW');
    }
}
