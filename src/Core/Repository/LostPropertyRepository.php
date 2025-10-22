<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\LostProperty;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<LostProperty>
 */
class LostPropertyRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return LostProperty::class;
    }

    public function findAllOrderedByDate(Hotel $hotel): array
    {
        return $this->createQueryBuilder('lp')
            ->where('lp.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('lp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}