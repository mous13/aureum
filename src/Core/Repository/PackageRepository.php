<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Package;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Package>
 */
class PackageRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Package::class;
    }
}