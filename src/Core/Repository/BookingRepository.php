<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Booking;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Booking>
 */
class BookingRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Booking::class;
    }
}
