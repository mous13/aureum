<?php

namespace Citadel\Aureum\Core\Entity;

enum FineStatus: string
{
    case NOT_APPEALED = 'not_appealed';
    case APPEAL_SUBMITTED = 'appeal_submitted';
    case APPEAL_COMPLETED = 'appeal_completed';

    public function getLabel(): string
    {
        return match($this) {
            self::NOT_APPEALED => 'Not Appealed',
            self::APPEAL_SUBMITTED => 'Appeal Submitted',
            self::APPEAL_COMPLETED => 'Appeal Completed',
        };
    }
}
