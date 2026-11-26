<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum StreetKind: string
{
    case MAJOR = 'major';
    case MINOR = 'minor';
    case PATH = 'path';

    public static function fromHighway(string $highway): ?self
    {
        return match (preg_replace('/_link$/', '', $highway)) {
            'motorway', 'trunk', 'primary', 'secondary', 'tertiary' => self::MAJOR,
            'residential', 'unclassified', 'living_street', 'service', 'road' => self::MINOR,
            'pedestrian', 'footway', 'path', 'steps', 'cycleway', 'track', 'bridleway' => self::PATH,
            default => null,
        };
    }

    public function prominence(): int
    {
        return match ($this) {
            self::MAJOR => 3,
            self::MINOR => 2,
            self::PATH => 1,
        };
    }
}
