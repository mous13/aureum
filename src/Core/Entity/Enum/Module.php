<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum Module: string
{
    case PACKAGES = 'packages';
    case BOOKINGS = 'bookings';
    case LOST_PROPERTY = 'lost_property';
    case FINES = 'fines';
    case RESTAURANTS = 'restaurants';
    case ROOMS = 'rooms';
    case EVENTS = 'events';
    case AMENITIES = 'amenities';

    public function getLabel(): string
    {
        return match ($this) {
            self::PACKAGES => 'Packages',
            self::BOOKINGS => 'Bookings',
            self::LOST_PROPERTY => 'Lost Property',
            self::FINES => 'Fines',
            self::RESTAURANTS => 'Restaurants',
            self::ROOMS => 'Rooms Directory',
            self::EVENTS => 'Events',
            self::AMENITIES => 'Amenities',
        };
    }

    public function permission(string $action): string
    {
        return "aureum.module.{$this->value}.{$action}";
    }

    /**
     * @return array<string>
     */
    public static function allPermissionKeys(): array
    {
        $keys = [];
        foreach (self::cases() as $module) {
            $keys[] = "{$module->value}.view";
            $keys[] = "{$module->value}.manage";
        }

        return $keys;
    }
}
