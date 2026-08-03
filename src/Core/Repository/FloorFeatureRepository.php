<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Floor;
use Citadel\Aureum\Core\Entity\FloorFeature;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<FloorFeature>
 */
class FloorFeatureRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return FloorFeature::class;
    }

    public function findByFloor(Floor $floor): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.floor = :floor')
            ->setParameter('floor', $floor)
            ->getQuery()
            ->getResult();
    }
}
