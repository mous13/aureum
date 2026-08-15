<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Entity\StockCount;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<StockCount>
 */
class StockCountRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return StockCount::class;
    }

    public function findLatestForInventory(Inventory $inventory): ?StockCount
    {
        return $this->createQueryBuilder('c')
            ->where('c.inventory = :inventory')
            ->setParameter('inventory', $inventory)
            ->orderBy('c.countedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
