<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Floor;
use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Floor>
 */
class FloorRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Floor::class;
    }

    public function findByHotelOrdered(Hotel $hotel): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
