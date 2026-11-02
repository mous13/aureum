<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Bar;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Bar>
 */
class BarRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Bar::class;
    }
}
