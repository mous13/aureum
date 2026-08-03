<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Floor;
use Citadel\Aureum\Core\Entity\Room;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Room>
 */
class RoomRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Room::class;
    }

    public function findByFloor(Floor $floor): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.floor = :floor')
            ->setParameter('floor', $floor)
            ->orderBy('r.number', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
