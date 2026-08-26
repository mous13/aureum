<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\AmenityBoard;
use Citadel\Aureum\Core\Entity\AmenityCardComment;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<AmenityCardComment>
 */
class AmenityCardCommentRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AmenityCardComment::class;
    }

    /** @return array<int, int> comment totals keyed by card id */
    public function countByBoard(AmenityBoard $board): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.card) AS cardId, COUNT(c.id) AS total')
            ->join('c.card', 'card')
            ->where('card.board = :board')
            ->setParameter('board', $board)
            ->groupBy('c.card')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['cardId']] = (int)$row['total'];
        }

        return $counts;
    }
}
