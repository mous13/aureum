<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Fine;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Fine>
 */
class FineRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Fine::class;
    }
}