<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum BedSize: string
{
    case SINGLE = 'Single';
    case DOUBLE = 'Double';
    case QUEEN = 'Queen';
    case King = 'King';
}
