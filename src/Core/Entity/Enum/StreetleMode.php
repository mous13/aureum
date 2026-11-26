<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum StreetleMode: string
{
    case SPRINT = 'sprint';
    case FREE_PLAY = 'free_play';

    public function getLabel(): string
    {
        return match ($this) {
            self::SPRINT => 'Sprint',
            self::FREE_PLAY => 'Free play',
        };
    }

    public function getTimeLimitSeconds(): ?int
    {
        return match ($this) {
            self::SPRINT => 300,
            self::FREE_PLAY => null,
        };
    }
}
