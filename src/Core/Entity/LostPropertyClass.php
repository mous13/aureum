<?php

namespace Citadel\Aureum\Core\Entity;

enum LostPropertyClass: string
{
    case LOST = 'lost';
    case FOUND = 'found';

    public function getLabel(): string
    {
        return match($this) {
            self::LOST => 'Lost',
            self::FOUND => 'Found',
        };
    }
}
