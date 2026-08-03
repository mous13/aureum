<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum FeatureType: string
{
    case HALLWAY = 'Hallway';
    case ELEVATOR = 'Elevator';
    case STORAGE = 'Storage';
    case STAIRS = 'Stairs';

    public function usesStorageFields(): bool
    {
        return $this === self::STORAGE;
    }
}
