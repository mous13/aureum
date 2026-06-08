<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Tag;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Tag>
 */
class TagRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Tag::class;
    }
}
