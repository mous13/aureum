<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\InventoryItemLog;

/**
 * @extends AbstractLogRepository<InventoryItemLog>
 */
class InventoryItemLogRepository extends AbstractLogRepository
{
    public static function getEntityClass(): string
    {
        return InventoryItemLog::class;
    }

    protected function getLogEntityReference(): string
    {
        return 'item';
    }

    /**
     * @return array<InventoryItemLog>
     */
    public function findByItem(InventoryItem $item): array
    {
        return $this->findByEntity($item);
    }
}
