<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Entity;

use Citadel\Aureum\Core\Entity\InventoryItem;
use PHPUnit\Framework\TestCase;

class InventoryItemTest extends TestCase
{
    private function item(?int $packSize, ?string $packLabel, string $unit = 'card'): InventoryItem
    {
        $item = new InventoryItem();
        $item->setName('Key Cards');
        $item->setUnit($unit);
        $item->setPackSize($packSize);
        $item->setPackLabel($packLabel);

        return $item;
    }

    public function testPacksForRoundsUp(): void
    {
        $item = $this->item(500, 'box');

        self::assertSame(1, $item->packsFor(1));
        self::assertSame(1, $item->packsFor(500));
        self::assertSame(2, $item->packsFor(501));
        self::assertSame(3, $item->packsFor(1200));
    }

    public function testPacksForWithoutPackSizeReturnsBaseUnits(): void
    {
        $item = $this->item(null, null, 'roll');

        self::assertSame(12, $item->packsFor(12));
    }

    public function testPacksForNeverReturnsNegative(): void
    {
        $item = $this->item(500, 'box');

        self::assertSame(0, $item->packsFor(0));
        self::assertSame(0, $item->packsFor(-250));
    }

    public function testDescribeQuantitySplitsIntoPacksAndLoose(): void
    {
        $item = $this->item(500, 'box');

        self::assertSame('8 box, 200 card', $item->describeQuantity(4200));
        self::assertSame('8 box', $item->describeQuantity(4000));
        self::assertSame('200 card', $item->describeQuantity(200));
    }

    public function testDescribeQuantityWithoutPackSizeUsesUnitOnly(): void
    {
        $item = $this->item(null, null, 'roll');

        self::assertSame('12 roll', $item->describeQuantity(12));
    }
}
