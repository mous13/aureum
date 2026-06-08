<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

/*
 * @extends AbstractRepository<Vote>
 */

use Citadel\Aureum\Core\Entity\Vote;
use Forumify\Core\Repository\AbstractRepository;

class VoteRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Vote::class;
    }

    public function findExisting(string $subjectType, int $subjectId, int $employeeId): ?Vote
    {
        return $this->findOneBy([
            'subjectType' => $subjectType,
            'subjectId' => $subjectId,
            'employee' => $employeeId,
        ]);
    }
}
