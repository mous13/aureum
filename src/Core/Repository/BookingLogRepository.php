<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Booking;
use Citadel\Aureum\Core\Entity\BookingLog;

/**
 * @extends AbstractLogRepository<BookingLog>
 */
class BookingLogRepository extends AbstractLogRepository
{
    public static function getEntityClass(): string
    {
        return BookingLog::class;
    }

    protected function getLogEntityReference(): string
    {
        return 'booking';
    }

    /**
     * @return array<BookingLog>
     */
    public function findByBooking(Booking $booking): array
    {
        return $this->findByEntity($booking);
    }
}
