<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleSession;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<StreetleSession>
 */
class StreetleSessionRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return StreetleSession::class;
    }

    public function findOwned(int $id, Employee $employee): ?StreetleSession
    {
        return $this->findOneBy(['id' => $id, 'employee' => $employee]);
    }

    /** @return list<StreetleSession> */
    public function findOpenForEmployee(Employee $employee): array
    {
        return $this->findBy(['employee' => $employee, 'endedAt' => null]);
    }

    /** @return list<StreetleSession> */
    public function findOpenForHotel(Hotel $hotel): array
    {
        return $this->findBy(['hotel' => $hotel, 'endedAt' => null]);
    }

    /** @return list<StreetleSession> */
    public function findScoredForBoard(Hotel $hotel, StreetleMode $mode): array
    {
        $sessions = $this->createQueryBuilder('s')
            ->join('s.employee', 'e')
            ->addSelect('e')
            ->where('s.hotel = :hotel')
            ->andWhere('s.mode = :mode')
            ->andWhere('s.endedAt IS NOT NULL')
            ->andWhere('s.correctCount > 0')
            ->andWhere('e.archivedAt IS NULL')
            ->setParameter('hotel', $hotel)
            ->setParameter('mode', $mode)
            ->getQuery()
            ->getResult();

        return is_array($sessions) ? array_values(array_filter($sessions, static fn (mixed $session): bool => $session instanceof StreetleSession)) : [];
    }
}
