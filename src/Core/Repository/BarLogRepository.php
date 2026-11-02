<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Entity\BarLog;

class BarLogRepository extends AbstractLogRepository
{
    public static function getEntityClass(): string
    {
        return BarLog::class;
    }

    protected function getLogEntityReference(): string
    {
        return 'bar';
    }

    public function findByBar(Bar $bar): array
    {
        return $this->findByEntity($bar);
    }
}
