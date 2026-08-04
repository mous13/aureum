<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Core\Repository\AbstractRepository;

abstract class AbstractLogRepository extends AbstractRepository
{
    public function getEntityName(): string
    {
        return static::getEntityName();
    }

    abstract protected function getLogEntityReference(): string;

    public function findByEntity(object $entity): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.' . $this->getLogEntityReference() . ' = :entity')
            ->setParameter('entity', $entity)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByHotel(Hotel $hotel, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('l.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByHotelBetween(Hotel $hotel, \DateTime $from, \DateTime $to): int
    {
        return (int)$this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.hotel = :hotel')
            ->andWhere('l.createdAt >= :from')
            ->andWhere('l.createdAt < :to')
            ->setParameter('hotel', $hotel)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
