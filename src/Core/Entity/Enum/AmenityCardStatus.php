<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum AmenityCardStatus: string
{
    case NOT_STARTED = 'not_started';
    case READY = 'ready';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Room Not Ready',
            self::READY => 'Room Ready',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
        };
    }

    public function next(): ?self
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);

        return $cases[$index + 1] ?? null;
    }
}
