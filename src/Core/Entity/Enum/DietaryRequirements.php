<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum DietaryRequirements: string
{
    case GLUTEN = 'Gluten Free Options';
    case VEGAN = 'Vegan Options';
    case VEGETARIAN = 'Vegetarian Options';
}
