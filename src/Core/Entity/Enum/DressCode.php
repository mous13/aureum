<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum DressCode: string
{
    case CASUAL = 'casual';
    case SMART_CASUAL = 'smart_casual';
    case SMART = 'smart';
    case FORMAL = 'formal';

    public function getLabel(): string
    {
        return match ($this) {
            self::CASUAL => 'Casual',
            self::SMART_CASUAL => 'Smart Casual',
            self::SMART => 'Smart',
            self::FORMAL => 'Formal',
        };
    }
}
