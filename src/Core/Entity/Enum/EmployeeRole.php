<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum EmployeeRole: string
{
    case MANAGER = 'Manager';
    case RECEPTION = 'Reception';
    case CONCIERGE = 'Concierge';
    case NIGHTS = 'Nights';

    public function canManageEvents(): bool
    {
        return match ($this) {
            self::MANAGER, self::CONCIERGE => true,
            self::RECEPTION, self::NIGHTS => false,
        };
    }
}
