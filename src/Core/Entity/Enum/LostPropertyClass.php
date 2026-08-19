<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum LostPropertyClass: string
{
    case LOST = 'lost';
    case FOUND = 'found';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOST => 'Lost',
            self::FOUND => 'Found',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::LOST => 'ph ph-map-pin-line',
            self::FOUND => 'ph ph-check-circle',
        };
    }
}
