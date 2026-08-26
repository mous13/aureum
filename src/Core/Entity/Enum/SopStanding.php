<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum SopStanding: string
{
    case NOT_APPLICABLE = 'not_applicable';
    case SIGN_OFF_NEEDED = 'sign_off_needed';
    case RECHECK_DUE = 'recheck_due';
    case CURRENT = 'current';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_APPLICABLE => 'Not required',
            self::SIGN_OFF_NEEDED => 'Sign off',
            self::RECHECK_DUE => 'Recheck due',
            self::CURRENT => 'Current',
        };
    }

    public function needsAction(): bool
    {
        return $this === self::SIGN_OFF_NEEDED || $this === self::RECHECK_DUE;
    }
}
