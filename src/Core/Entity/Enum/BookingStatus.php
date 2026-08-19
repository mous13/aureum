<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum BookingStatus: string
{
    case UNCONFIRMED = 'unconfirmed';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNCONFIRMED => 'Unconfirmed',
            self::CONFIRMED => 'Confirmed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::UNCONFIRMED || $this === self::CONFIRMED;
    }

    /**
     * @return array<string, self>
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $status) {
            $choices[$status->getLabel()] = $status;
        }

        return $choices;
    }
}
