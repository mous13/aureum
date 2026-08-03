<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\RoomType;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<RoomType>
 */
class RoomTypeRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return RoomType::class;
    }

    /***
     * @return array<RoomType>
     */
    public function findSelectableByHotel(Hotel $hotel): array
    {
        return $this->findBy(['hotel' => $hotel, 'archived' => false], ['name' => 'ASC']);
    }

    /**
     * @return array<RoomType>
     */
    public function findAllByHotel(Hotel $hotel): array
    {
        return $this->findBy(['hotel' => $hotel], ['name' => 'ASC']);
    }

    public function findOneForHotel(int $id, Hotel $hotel): ?RoomType
    {
        return $this->findOneBy(['id' => $id, 'hotel' => $hotel]);
    }
}
