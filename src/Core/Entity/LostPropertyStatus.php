<?php

namespace Citadel\Aureum\Core\Entity;

enum LostPropertyStatus: string
{
    case OPEN = 'open';
    case COLLECTED = 'collected';
    case POSTED = 'posted';
    case DISPOSED = 'disposed';
    case WAITING_COLLECTION = "waiting for collection";
    case WAITING_POSTED = "waiting to be posted";
    case STORED = "stored";

    public function getLabel(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::COLLECTED => 'collected',
            self::POSTED => 'Posted',
            self::DISPOSED => 'Disposed',
            self::WAITING_COLLECTION => 'waiting for collection',
            self::WAITING_POSTED => 'Waiting to be posted',
            self::STORED => 'Stored',
        };
    }
}
