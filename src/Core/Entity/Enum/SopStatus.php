<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum SopStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    public function isActionable(): bool
    {
        return $this === self::PUBLISHED;
    }
}
