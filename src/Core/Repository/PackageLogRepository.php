<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Package;
use Citadel\Aureum\Core\Entity\PackageLog;
use Citadel\Aureum\Core\Repository\AbstractLogRepository;

/**
 * @extends AbstractLogRepository<PackageLog>
 */
class PackageLogRepository extends AbstractLogRepository
{
    public static function getEntityClass(): string
    {
        return PackageLog::class;
    }

    protected function getLogEntityReference(): string
    {
        return 'package';
    }

    public function findByPackage(Package $package): array
    {
        return $this->findByEntity($package);
    }
}