<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Enum\SopStatus;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Sop;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Sop>
 */
class SopRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Sop::class;
    }

    /** @return array<Sop> */
    public function searchForHotel(
        Hotel $hotel,
        string $term,
        ?int $categoryId,
        bool $includeDrafts,
        bool $includeArchived,
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.category', 'c')
            ->addSelect('c')
            ->where('s.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->orderBy('s.title', 'ASC');

        $statuses = [SopStatus::PUBLISHED->value];
        if ($includeDrafts) {
            $statuses[] = SopStatus::DRAFT->value;
        }
        if ($includeArchived) {
            $statuses[] = SopStatus::ARCHIVED->value;
        }
        $qb->andWhere('s.status IN (:statuses)')->setParameter('statuses', $statuses);

        if ($term !== '') {
            $qb->andWhere('s.title LIKE :term OR s.bodyText LIKE :term')
                ->setParameter('term', '%' . addcslashes($term, '%_\\') . '%');
        }

        if ($categoryId !== null) {
            $qb->andWhere('s.category = :category')->setParameter('category', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }
}
