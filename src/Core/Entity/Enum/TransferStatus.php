<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum TransferStatus: string
{
    case UNCONFIRMED = 'unconfirmed';
    case CONFIRMED = 'confirmed';

    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNCONFIRMED => 'unconfirmed',
            self::CONFIRMED => 'Confirmed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
