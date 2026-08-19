<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\LogEntityTrait;
use Citadel\Aureum\Core\Repository\BookingLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: BookingLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_bookings')]
#[ORM\Index(name: 'idx_booking_log_hotel_created', columns: ['hotel_id', 'created_at'])]
#[ORM\Index(name: 'idx_booking_log_booking_created', columns: ['booking_id', 'created_at'])]
class BookingLog
{
    use IdentifiableEntityTrait;
    use LogEntityTrait;

    #[ORM\ManyToOne(targetEntity: Booking::class)]
    private Booking $booking;

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function setBooking(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function getEntityType(): string
    {
        return 'booking';
    }

    public function getEntityId(): int
    {
        return $this->booking->getId();
    }
}
