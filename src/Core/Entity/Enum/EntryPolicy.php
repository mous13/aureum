<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum EntryPolicy: string
{
    case WALK_INS = 'walk_ins';
    case RESERVATIONS = 'reservations';
    case BOTH = 'both';

    public function getLabel(): string
    {
        return match ($this) {
            self::WALK_INS => 'Walk-ins only',
            self::RESERVATIONS => 'Reservations only',
            self::BOTH => 'Walk-ins & reservations',
        };
    }
}
