<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Event;
use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Event>
 */
class EventRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Event::class;
    }

    /**
     * @return array<Event>
     */
    public function findByHotel(Hotel $hotel): array
    {
        return $this->findBy(['hotel' => $hotel]);
    }

    public function findForWeek(Hotel $hotel, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.hotel = :hotel')
            ->andWhere('e.start >= :start')
            ->andWhere('e.start < :end')
            ->setParameter('hotel', $hotel)
            ->setParameter('start', $start)
            ->setParameter('end', $end->modify('+1 day'))
            ->orderBy('e.start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRestaurantsWithActiveEvents(Hotel $hotel): array
    {
        return $this->findVenueIdsWithEventsToday($hotel, 'restaurant');
    }

    public function findBarsWithActiveEvents(Hotel $hotel): array
    {
        return $this->findVenueIdsWithEventsToday($hotel, 'bar');
    }

    private function findVenueIdsWithEventsToday(Hotel $hotel, string $association): array
    {
        $today = new \DateTimeImmutable('today');

        $result = $this->createQueryBuilder('e')
            ->select("DISTINCT IDENTITY(e.{$association}) AS venueId")
            ->where('e.hotel = :hotel')
            ->andWhere("e.{$association} IS NOT NULL")
            ->andWhere('e.start >= :todayStart')
            ->andWhere('e.start < :tomorrowStart')
            ->setParameter('hotel', $hotel)
            ->setParameter('todayStart', $today)
            ->setParameter('tomorrowStart', $today->modify('+1 day'))
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row) => (int) $row['venueId'], $result);
    }
}
