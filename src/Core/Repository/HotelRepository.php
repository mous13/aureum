<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Exception;
use Citadel\Aureum\Core\Entity\Hotel;

/**
 * @extends AbstractRepository<Hotel>
 */
class HotelRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Hotel::class;
    }
}