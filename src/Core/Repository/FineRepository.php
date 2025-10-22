<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Fine>
 */
class FineRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Fine::class;
    }

    public function findAllOrderedByDate(Hotel $hotel): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}