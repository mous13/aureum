<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Enum\StorageLocationType;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<InventoryItem>
 */
class InventoryItemRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return InventoryItem::class;
    }

    /**
     * @return array<InventoryItem>
     */
    public function findActiveByHotel(Hotel $hotel): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.category', 'c')
            ->join('c.inventory', 'inv')
            ->join('i.location', 'l')
            ->where('inv.hotel = :hotel')
            ->andWhere('i.active = true')
            ->andWhere('inv.active = true')
            ->andWhere('l.type = :locationType')
            ->setParameter('hotel', $hotel)
            ->setParameter('locationType', StorageLocationType::BULK)
            ->orderBy('inv.position', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->addOrderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<InventoryItem>
     */
    public function findActiveByInventory(Inventory $inventory): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.category', 'c')
            ->where('c.inventory = :inventory')
            ->andWhere('i.active = true')
            ->setParameter('inventory', $inventory)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
