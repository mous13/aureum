<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Inventory;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Inventory>
 */
class InventoryRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Inventory::class;
    }

    /**
     * @return array<Inventory>
     */
    public function findActiveByHotel(Hotel $hotel): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.hotel = :hotel')
            ->andWhere('i.active = true')
            ->setParameter('hotel', $hotel)
            ->orderBy('i.position', 'ASC')
            ->addOrderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
