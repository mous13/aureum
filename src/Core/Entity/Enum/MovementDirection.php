<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum MovementDirection: string
{
    case IN = 'in';
    case OUT = 'out';

    public function getLabel(): string
    {
        return match ($this) {
            self::IN => 'In',
            self::OUT => 'Out',
        };
    }

    public function sign(): int
    {
        return $this === self::IN ? 1 : -1;
    }
}
