<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Exception;
use Citadel\Aureum\Core\Entity\Transfer;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Transfer>
 */
class TransferRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Transfer::class;
    }
}