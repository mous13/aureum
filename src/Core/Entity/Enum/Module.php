<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum Module: string
{
    case PACKAGES = 'packages';
    case TRANSFERS = 'transfers';
    case LOST_PROPERTY = 'lost_property';
    case FINES = 'fines';
    case RESTAURANTS = 'restaurants';
    case ROOMS = 'rooms';
    case EVENTS = 'events';
    case INVENTORY = 'inventory';

    public function getLabel(): string
    {
        return match ($this) {
            self::PACKAGES => 'Packages',
            self::TRANSFERS => 'Transfers',
            self::LOST_PROPERTY => 'Lost Property',
            self::FINES => 'Fines',
            self::RESTAURANTS => 'Restaurants',
            self::ROOMS => 'Rooms Directory',
            self::EVENTS => 'Events',
            self::INVENTORY => 'Inventory',
        };
    }

    public function permission(string $action): string
    {
        return "aureum.module.{$this->value}.{$action}";
    }

    /**
     * @return array<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::INVENTORY => ['view', 'count', 'manage'],
            default => ['view', 'manage'],
        };
    }

    /**
     * @return array<string>
     */
    public static function allPermissionKeys(): array
    {
        $keys = [];
        foreach (self::cases() as $module) {
            foreach ($module->permissions() as $action) {
                $keys[] = "{$module->value}.{$action}";
            }
        }

        return $keys;
    }
}
