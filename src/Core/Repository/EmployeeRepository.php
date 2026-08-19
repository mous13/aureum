<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Employee;
use Forumify\Core\Repository\AbstractRepository;
use Citadel\Aureum\Core\Entity\Hotel;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends AbstractRepository<Employee>
 */
class EmployeeRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Employee::class;
    }

    public function createQueryBuilderForHotel(?int $hotelId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.hotel', 'h')
            ->andWhere('e.archivedAt IS NULL');

        if ($hotelId !== null) {
            $qb->andWhere('h.id = :hotelId')
                ->setParameter('hotelId', $hotelId);
        }

        return $qb;
    }

    /**
     * @return array<Employee>
     */
    public function findByHotel(Hotel $hotel): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.hotelRoles', 'r')
            ->addSelect('r')
            ->where('e.hotel = :hotel')
            ->andWhere('e.archivedAt IS NULL')
            ->setParameter('hotel', $hotel)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByHotel(Hotel $hotel): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.hotel = :hotel')
            ->andWhere('e.archivedAt IS NULL')
            ->setParameter('hotel', $hotel)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
