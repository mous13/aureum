<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum EventType: string
{
    case MUSIC_NIGHTLIFE = 'music_nightlife';
    case THEATRE_ENTERTAINMENT = 'theatre_entertainment';
    case sports_activities = 'sports_activities';
    case food_drink = 'food_drink';
    case arts_culture = 'arts_culture';
    case family_seasonal = 'family_seasonal';

    public function getLabel(): string
    {
        return match ($this) {
            self::MUSIC_NIGHTLIFE => 'Music & Nightlife',
            self::THEATRE_ENTERTAINMENT => 'Theatre & Entertainment',
            self::sports_activities => 'Sports & Activities',
            self::food_drink => 'Food & Drink',
            self::arts_culture => 'Arts & Culture',
            self::family_seasonal => 'Family & Seasonal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MUSIC_NIGHTLIFE => '#6D5A8D',
            self::THEATRE_ENTERTAINMENT => '#8A4F5E',
            self::sports_activities => '#3F7391',
            self::food_drink => '#A57C3F',
            self::arts_culture => '#5F7D70',
            self::family_seasonal => '#B46F52',
        };
    }
}
