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
            ->andWhere('e.eventDate >= :start')
            ->andWhere('e.eventDate < :end')
            ->setParameter('hotel', $hotel)
            ->setParameter('start', $start)
            ->setParameter('end', $end->modify('+1 day'))
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
