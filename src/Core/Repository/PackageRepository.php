<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Package;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Package>
 */
class PackageRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Package::class;
    }

    public function findAllOrderedByDate(Hotel $hotel): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}