<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\FineLog;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<FineLog>
 */
class FineLogRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return FineLog::class;
    }

    public function findByFine(Fine $fine): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.fine = :fine')
            ->setParameter('fine', $fine)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByHotel(Hotel $hotel): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}