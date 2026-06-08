<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Cuisine;
use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Cuisine>
 */
class CuisineRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Cuisine::class;
    }

    /**
     * @return array<Cuisine>
     */
    public function findByHotel(Hotel $hotel): array
    {
        return $this->findBy(['hotel' => $hotel]);
    }
}
