<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Entity\LostPropertyLog;
use Citadel\Aureum\Core\Repository\AbstractLogRepository;

/**
 * @extends AbstractLogRepository<LostPropertyLog>
 */
class LostPropertyLogRepository extends AbstractLogRepository
{
    public static function getEntityClass(): string
    {
        return LostPropertyLog::class;
    }

    protected function getLogEntityReference(): string
    {
        return 'lostProperty';
    }

    public function findByLostProperty(LostProperty $lostProperty): array
    {
        return $this->findByEntity($lostProperty);
    }
}