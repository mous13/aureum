<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: VoteRepository::class)]
#[ORM\Table(name: 'aureum_votes')]
#[ORM\UniqueConstraint(name: 'unique_vote', columns: ['employee_id', 'subject_type', 'subject_id'])]
class Vote
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false)]
    private Employee $employee;

    #[ORM\Column(length: 50)]
    private string $subjectType;

    #[ORM\Column]
    private int $subjectId;

    #[ORM\Column(length: 10)]
    private string $type;

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    public function setSubjectType(string $subjectType): void
    {
        $this->subjectType = $subjectType;
    }

    public function getSubjectId(): int
    {
        return $this->subjectId;
    }

    public function setSubjectId(int $subjectId): void
    {
        $this->subjectId = $subjectId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
