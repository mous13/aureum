<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Controller;

use Citadel\Aureum\Core\Controller\InventoryController;
use Citadel\Aureum\Core\Entity\Enum\ReorderStatus;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Service\Forecast\ItemForecast;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ReorderGroupingTest extends TestCase
{
    private function forecast(?string $orderBy, ReorderStatus $status): ItemForecast
    {
        $item = new InventoryItem();
        $item->setName('Key Cards');
        $item->setUnit('card');

        return new ItemForecast(
            $item,
            100,
            10.0,
            10.0,
            $orderBy === null ? null : new DateTimeImmutable($orderBy),
            null,
            null,
            $status,
            false,
            false,
            5,
            35.0,
        );
    }

    public function testForecastsAreGroupedByIsoWeek(): void
    {
        $groups = InventoryController::groupByWeek([
            $this->forecast('2026-08-24', ReorderStatus::ORDER_NOW),
            $this->forecast('2026-08-26', ReorderStatus::DUE_SOON),
            $this->forecast('2026-09-02', ReorderStatus::DUE_SOON),
        ]);

        self::assertSame(['2026-W35', '2026-W36'], array_keys($groups));
        self::assertCount(2, $groups['2026-W35']);
        self::assertCount(1, $groups['2026-W36']);
    }

    public function testItemsWithNoOrderDateAreGroupedSeparatelyAndLast(): void
    {
        $groups = InventoryController::groupByWeek([
            $this->forecast(null, ReorderStatus::NEEDS_SETUP),
            $this->forecast('2026-08-24', ReorderStatus::ORDER_NOW),
        ]);

        self::assertSame(['2026-W35', ''], array_keys($groups));
    }

    public function testEmptyInputGivesNoGroups(): void
    {
        self::assertSame([], InventoryController::groupByWeek([]));
    }
}
