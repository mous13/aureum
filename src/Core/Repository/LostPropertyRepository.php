<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\LostProperty;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<LostProperty>
 */
class LostPropertyRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return LostProperty::class;
    }
}