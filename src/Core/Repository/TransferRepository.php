<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Exception;
use Citadel\Aureum\Core\Entity\Transfer;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Transfer>
 */
class TransferRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Transfer::class;
    }

    public function findAllOrderedByDate(Hotel $hotel): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}