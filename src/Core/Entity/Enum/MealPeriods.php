<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum MealPeriods: string
{
    case BREAKFAST = 'Breakfast';
    case BRUNCH = 'Brunch';
    case LUNCH = 'Lunch';
    case DINNER = 'Dinner';
}
