<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum BathroomStyle: string
{
    case BATH_SHOWER = "Bath & Shower";
    case BATH = "Bath Only";
    case SHOWER = "Shower Only";
}
