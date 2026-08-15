<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Entity\InventoryCategory;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<InventoryCategory>
 */
class InventoryCategoryRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return InventoryCategory::class;
    }

    /**
     * @return array<InventoryCategory>
     */
    public function findByInventory(Inventory $inventory): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.inventory = :inventory')
            ->setParameter('inventory', $inventory)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<InventoryCategory>
     */
    public function findByHotel(Hotel $hotel): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.inventory', 'i')
            ->where('i.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('i.position', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
