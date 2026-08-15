<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum ReorderStatus: string
{
    case NEEDS_SETUP = 'needs_setup';
    case NO_DATA = 'no_data';
    case OVERDUE = 'overdue';
    case ORDER_NOW = 'order_now';
    case DUE_SOON = 'due_soon';
    case OK = 'ok';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEEDS_SETUP => 'Needs Setup',
            self::NO_DATA => 'Collecting Data',
            self::OVERDUE => 'Overdue',
            self::ORDER_NOW => 'Order Now',
            self::DUE_SOON => 'Due Soon',
            self::OK => 'OK',
        };
    }

    public function isActionable(): bool
    {
        return $this === self::OVERDUE || $this === self::ORDER_NOW;
    }

    public function sortWeight(): int
    {
        return match ($this) {
            self::OVERDUE => 0,
            self::ORDER_NOW => 1,
            self::DUE_SOON => 2,
            self::NEEDS_SETUP => 3,
            self::NO_DATA => 4,
            self::OK => 5,
        };
    }
}
