<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum PackageStatus: string
{
    case RECEIVED = 'received';
    case PICKED_UP = 'picked_up';

    public function getLabel(): string
    {
        return match ($this) {
            self::RECEIVED => 'Received',
            self::PICKED_UP => 'Picked Up',
        };
    }
}
