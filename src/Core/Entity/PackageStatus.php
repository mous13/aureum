<?php

namespace Citadel\Aureum\Core\Entity;

enum PackageStatus: string
{
    case RECEIVED = 'received';
    case PICKED_UP = 'picked_up';

    public function getLabel(): string
    {
        return match($this) {
            self::RECEIVED => 'Received',
            self::PICKED_UP => 'Picked Up',
        };
    }
}