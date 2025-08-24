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
            ->leftJoin('e.hotel', 'h');

        if ($hotelId !== null) {
            $qb->andWhere('e.hotel = :hotelId')
                ->setParameter('hotelId', $hotelId);
        }

        return $qb;
    }

    /**
     * @return Employee[]
     */
    public function findByHotel(Hotel $hotel): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.hotel = :hotel')
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
            ->setParameter('hotel', $hotel)
            ->getQuery()
            ->getSingleScalarResult();
    }
}