<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum StorageLocationType: string
{
    case BULK = 'bulk';
    case WORKING = 'working';

    public function getLabel(): string
    {
        return match ($this) {
            self::BULK => 'Bulk Store',
            self::WORKING => 'Working Location',
        };
    }

    public function isForecast(): bool
    {
        return $this === self::BULK;
    }
}
