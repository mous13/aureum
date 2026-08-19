<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\AccessLog;
use Citadel\Aureum\Core\Entity\Hotel;
use DateTimeInterface;
use Forumify\Core\Repository\AbstractRepository;

/** @extends AbstractRepository<AccessLog> */
class AccessLogRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AccessLog::class;
    }

    /** @return array<AccessLog> */
    public function findRecentForHotel(Hotel $hotel, int $limit = 200): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('a.accessedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function deleteOlderThan(DateTimeInterface $cutoff): int
    {
        return (int)$this->createQueryBuilder('a')
            ->delete()
            ->where('a.accessedAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }
}
