<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Enum\StorageLocationType;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<StorageLocation>
 */
class StorageLocationRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return StorageLocation::class;
    }

    /**
     * @return array<StorageLocation>
     */
    public function findActiveByHotel(Hotel $hotel, ?StorageLocationType $type = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.hotel = :hotel')
            ->andWhere('l.active = true')
            ->setParameter('hotel', $hotel)
            ->orderBy('l.position', 'ASC')
            ->addOrderBy('l.name', 'ASC');

        if ($type !== null) {
            $qb->andWhere('l.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }
}
