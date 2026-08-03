<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum RoomView: string
{
    case INTERNAL = 'Internal';
    case EXTERNAL = 'External';
}
