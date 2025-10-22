<?php

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Transfer;
use Citadel\Aureum\Core\Entity\TransferLog;
use Citadel\Aureum\Core\Repository\AbstractLogRepository;

/**
 * @extends AbstractLogRepository<TransferLog>
 */
class TransferLogRepository extends AbstractLogRepository
{
    public static function getEntityClass(): string
    {
        return TransferLog::class;
    }

    protected function getLogEntityReference(): string
    {
        return 'transfer';
    }

    public function findByTransfer(Transfer $transfer): array
    {
        return $this->findByEntity($transfer);
    }
}