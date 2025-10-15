<?php

namespace Citadel\Aureum\Core\Entity;

enum LogAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case STATUS_CHANGED = 'status_changed';

    public function getLabel(): string
    {
        return match($this) {
            self::CREATED => 'Created',
            self::UPDATED => 'Updated',
            self::DELETED => 'Deleted',
            self::STATUS_CHANGED => 'Status Changed',
        };
    }
}
