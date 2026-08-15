<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum MovementReason: string
{
    case DELIVERY = 'delivery';
    case TRANSFER = 'transfer';
    case WASTAGE = 'wastage';
    case ADJUSTMENT = 'adjustment';

    public function getLabel(): string
    {
        return match ($this) {
            self::DELIVERY => 'Delivery',
            self::TRANSFER => 'Transfer',
            self::WASTAGE => 'Wastage',
            self::ADJUSTMENT => 'Adjustment',
        };
    }

    public function defaultDirection(): ?MovementDirection
    {
        return match ($this) {
            self::DELIVERY => MovementDirection::IN,
            self::TRANSFER, self::WASTAGE => MovementDirection::OUT,
            self::ADJUSTMENT => null,
        };
    }
}
