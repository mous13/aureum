<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Vote;
use Citadel\Aureum\Core\Repository\VoteRepository;
use Forumify\Core\Repository\AbstractRepository;

class VotingService
{
    public function __construct(
        private readonly VoteRepository $voteRepository,
    ) {
    }

    public function vote(object $subject, Employee $employee, string $type, AbstractRepository $repository): void
    {

        $subjectType = strtolower((new \ReflectionClass($subject))->getShortName());
        $existing = $this->voteRepository->findExisting($subjectType, $subject->getId(), $employee->getId());

        if ($existing) {
            if ($existing->getType() === $type) {
                $this->reverseVote($subject, $type);
                $this->voteRepository->remove($existing);
            } else {
                $this->reverseVote($subject, $existing->getType());
                $this->applyVote($subject, $type);
                $existing->setType($type);
                $this->voteRepository->save($existing);
            }
        } else {
            $vote = new Vote();
            $vote->setEmployee($employee);
            $vote->setSubjectType($subjectType);
            $vote->setSubjectId($subject->getId());
            $vote->setType($type);
            $this->voteRepository->save($vote);
            $this->applyVote($subject, $type);
        }

        $repository->save($subject);
    }

    private function applyVote(object $subject, string $type): void
    {
        match ($type) {
            'up' => $subject->upvote(),
            'down' => $subject->downvote(),
        };
    }

    private function reverseVote(object $subject, string $type): void
    {
        match ($type) {
            'up' => $subject->setScore($subject->getScore() - 2),
            'down' => $subject->setScore($subject->getScore() + 1),
        };
    }
}
