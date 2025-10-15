<?php

namespace Citadel\Aureum\Core\Entity;

enum TransferStatus: string
{
    case UNCONFIRMED = 'unconfirmed';
    case CONFIRMED = 'confirmed';

    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::UNCONFIRMED => 'unconfirmed',
            self::CONFIRMED => 'Confirmed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
