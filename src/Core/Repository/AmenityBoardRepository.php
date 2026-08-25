<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\AmenityBoard;
use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<AmenityBoard>
 */
class AmenityBoardRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AmenityBoard::class;
    }

    public function findLatest(Hotel $hotel): ?AmenityBoard
    {
        return $this->createQueryBuilder('b')
            ->where('b.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('b.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return array<AmenityBoard> */
    public function findAllByHotel(Hotel $hotel): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.cards', 'c')
            ->addSelect('c')
            ->where('b.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('b.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
