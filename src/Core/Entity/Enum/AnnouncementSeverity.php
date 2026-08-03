<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum AnnouncementSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function getLabel(): string
    {
        return match ($this) {
            self::INFO => 'Info',
            self::WARNING => 'Warning',
            self::CRITICAL => 'Critical',
        };
    }

    public function getAlertClass(): string
    {
        return match ($this) {
            self::INFO => 'alert-info',
            self::WARNING => 'alert-warning',
            self::CRITICAL => 'alert-error',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::INFO => 'ph ph-info',
            self::WARNING => 'ph ph-warning',
            self::CRITICAL => 'ph ph-warning-octagon',
        };
    }
}
