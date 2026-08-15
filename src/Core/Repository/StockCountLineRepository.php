<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StockCountLine;
use DateTimeInterface;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<StockCountLine>
 */
class StockCountLineRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return StockCountLine::class;
    }

    /**
     * @return array<StockCountLine>
     */
    public function findForItemSince(InventoryItem $item, DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.stockCount', 'c')
            ->addSelect('c')
            ->where('l.item = :item')
            ->andWhere('c.countedAt >= :since')
            ->setParameter('item', $item)
            ->setParameter('since', $since)
            ->orderBy('c.countedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<StockCountLine>
     */
    public function findForHotelSince(Hotel $hotel, DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.stockCount', 'c')
            ->addSelect('c')
            ->join('l.item', 'i')
            ->addSelect('i')
            ->where('c.hotel = :hotel')
            ->andWhere('c.countedAt >= :since')
            ->setParameter('hotel', $hotel)
            ->setParameter('since', $since)
            ->orderBy('c.countedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
