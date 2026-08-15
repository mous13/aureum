<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\MovementDirection;
use Citadel\Aureum\Core\Entity\Enum\MovementReason;
use Citadel\Aureum\Core\Entity\StockMovement;
use PHPUnit\Framework\TestCase;

class StockMovementTest extends TestCase
{
    public function testDeliveryIsPositive(): void
    {
        $movement = new StockMovement();
        $movement->setDirection(MovementDirection::IN);
        $movement->setReason(MovementReason::DELIVERY);
        $movement->setQuantity(500);

        self::assertSame(500, $movement->getSignedQuantity());
    }

    public function testTransferIsNegative(): void
    {
        $movement = new StockMovement();
        $movement->setDirection(MovementDirection::OUT);
        $movement->setReason(MovementReason::TRANSFER);
        $movement->setQuantity(300);

        self::assertSame(-300, $movement->getSignedQuantity());
    }

    public function testDefaultDirectionForReason(): void
    {
        self::assertSame(MovementDirection::IN, MovementReason::DELIVERY->defaultDirection());
        self::assertSame(MovementDirection::OUT, MovementReason::TRANSFER->defaultDirection());
        self::assertSame(MovementDirection::OUT, MovementReason::WASTAGE->defaultDirection());
        self::assertNull(MovementReason::ADJUSTMENT->defaultDirection());
    }
}
