<?php

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