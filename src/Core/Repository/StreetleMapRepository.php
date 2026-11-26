<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleMap;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<StreetleMap>
 */
class StreetleMapRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return StreetleMap::class;
    }

    public function findOneByHotel(Hotel $hotel): ?StreetleMap
    {
        return $this->findOneBy(['hotel' => $hotel]);
    }
}
