<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Entity\LostPropertyLog;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<LostPropertyLog>
 */
class LostPropertyLogRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return LostPropertyLog::class;
    }

    public function findByLostProperty(LostProperty $lostProperty): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.lostProperty = :property')
            ->setParameter('property', $lostProperty)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByHotel(Hotel $hotel): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}