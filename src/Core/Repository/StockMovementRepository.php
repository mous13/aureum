<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StockMovement;
use DateTimeInterface;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<StockMovement>
 */
class StockMovementRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return StockMovement::class;
    }

    /**
     * @return array<StockMovement>
     */
    public function findForItemSince(InventoryItem $item, DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.item = :item')
            ->andWhere('m.occurredAt >= :since')
            ->setParameter('item', $item)
            ->setParameter('since', $since)
            ->orderBy('m.occurredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<StockMovement>
     */
    public function findForHotelSince(Hotel $hotel, DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.item', 'i')
            ->addSelect('i')
            ->where('m.hotel = :hotel')
            ->andWhere('m.occurredAt >= :since')
            ->setParameter('hotel', $hotel)
            ->setParameter('since', $since)
            ->orderBy('m.occurredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
