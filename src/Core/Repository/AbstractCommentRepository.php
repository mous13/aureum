<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Forumify\Core\Repository\AbstractRepository;

abstract class AbstractCommentRepository extends AbstractRepository
{
    abstract protected function getSubjectReference(): string;

    public function findBySubject(object $subject): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('author')
            ->join('c.author', 'author')
            ->where('c.' . $this->getSubjectReference() . ' = :subject')
            ->setParameter('subject', $subject)
            ->orderBy('c.createdAt', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countBySubjectIds(array $subjectIds): array
    {
        if ($subjectIds === []) {
            return [];
        }

        $reference = $this->getSubjectReference();
        $rows = $this->createQueryBuilder('c')
            ->select("IDENTITY(c.$reference) AS subjectId, COUNT(c.id) AS total")
            ->where("c.$reference IN (:subjects)")
            ->setParameter('subjects', $subjectIds)
            ->groupBy("c.$reference")
            ->getQuery()
            ->getScalarResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['subjectId']] = (int)$row['total'];
        }

        return $counts;
    }
}
