<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\AmenityCard;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<AmenityCard>
 */
class AmenityCardRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AmenityCard::class;
    }
}
