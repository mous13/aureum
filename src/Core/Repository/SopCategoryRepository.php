<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\SopCategory;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<SopCategory>
 */
class SopCategoryRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return SopCategory::class;
    }
}
