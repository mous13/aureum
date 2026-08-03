<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\HotelRole;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<HotelRole>
 */
class HotelRoleRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return HotelRole::class;
    }

    /**
     * @return array<HotelRole>
     */
    public function findByHotel(Hotel $hotel): array
    {
        return $this->findBy(['hotel' => $hotel], ['name' => 'ASC']);
    }
}
