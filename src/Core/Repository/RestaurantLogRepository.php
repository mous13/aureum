<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

/*
 * @extends AbstractLogRepository<RestaurantLog>
 */

use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Entity\RestaurantLog;

class RestaurantLogRepository extends AbstractLogRepository
{
    public static function getEntityClass(): string
    {
        return RestaurantLog::class;
    }

    protected function getLogEntityReference(): string
    {
        return 'restaurant';
    }

    public function findByRestaurant(Restaurant $restaurant): array
    {
        return $this->findByEntity($restaurant);
    }
}
