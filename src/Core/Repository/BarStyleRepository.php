<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\BarStyle;
use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<BarStyle>
 */
class BarStyleRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return BarStyle::class;
    }

    /**
     * @return array<BarStyle>
     */
    public function findByHotel(Hotel $hotel): array
    {
        return $this->findBy(['hotel' => $hotel]);
    }
}
