<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Restaurant;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Restaurant>
 */
class RestaurantRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Restaurant::class;
    }

    public function findAllByHotelOrderedByScore(Hotel $hotel): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('r.score', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
