<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\MovementDirection;
use Citadel\Aureum\Core\Entity\Enum\MovementReason;
use Citadel\Aureum\Core\Form\StockMovementType;
use PHPUnit\Framework\TestCase;

class StockMovementTypeTest extends TestCase
{
    public function testEveryReasonIsOffered(): void
    {
        self::assertSame(
            [
                'Delivery' => MovementReason::DELIVERY,
                'Transfer' => MovementReason::TRANSFER,
                'Wastage' => MovementReason::WASTAGE,
                'Adjustment' => MovementReason::ADJUSTMENT,
            ],
            StockMovementType::reasonChoices(),
        );
    }

    public function testDirectionIsDerivedFromReasonWhereItIsUnambiguous(): void
    {
        self::assertSame(MovementDirection::IN, StockMovementType::directionFor(MovementReason::DELIVERY, MovementDirection::OUT));
        self::assertSame(MovementDirection::OUT, StockMovementType::directionFor(MovementReason::TRANSFER, MovementDirection::IN));
        self::assertSame(MovementDirection::OUT, StockMovementType::directionFor(MovementReason::WASTAGE, MovementDirection::IN));
        self::assertSame(MovementDirection::IN, StockMovementType::directionFor(MovementReason::ADJUSTMENT, MovementDirection::IN));
        self::assertSame(MovementDirection::OUT, StockMovementType::directionFor(MovementReason::ADJUSTMENT, MovementDirection::OUT));
    }
}
