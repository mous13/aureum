<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\FineLog;

/**
 * @extends AbstractLogRepository<FineLog>
 */
class FineLogRepository extends AbstractLogRepository
{
    public static function getEntityClass(): string
    {
        return FineLog::class;
    }

    protected function getLogEntityReference(): string
    {
        return 'fine';
    }

    public function findByFine(Fine $fine): array
    {
        return $this->findByEntity($fine);
    }
}