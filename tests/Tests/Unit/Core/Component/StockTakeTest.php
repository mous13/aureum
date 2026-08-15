<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Component;

use Citadel\Aureum\Core\Component\StockTake;
use PHPUnit\Framework\TestCase;

class StockTakeTest extends TestCase
{
    public function testPacksAndLooseCombineIntoBaseUnits(): void
    {
        self::assertSame(4200, StockTake::baseUnits(8, 200, 500));
    }

    public function testLooseOnlyWhenThereIsNoPackSize(): void
    {
        self::assertSame(12, StockTake::baseUnits(0, 12, null));
    }

    public function testPacksAreIgnoredWithoutAPackSize(): void
    {
        self::assertSame(12, StockTake::baseUnits(5, 12, null));
    }

    public function testNegativeInputsAreTreatedAsZero(): void
    {
        self::assertSame(0, StockTake::baseUnits(-3, -10, 500));
        self::assertSame(500, StockTake::baseUnits(1, -10, 500));
    }

    public function testZeroIsAValidCount(): void
    {
        self::assertSame(0, StockTake::baseUnits(0, 0, 500));
    }
}
