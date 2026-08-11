<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Trait;

use Citadel\Aureum\Core\Entity\Employee;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait CommentEntityTrait
{
    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $author;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $editedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getAuthor(): Employee
    {
        return $this->author;
    }

    public function setAuthor(Employee $author): void
    {
        $this->author = $author;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getEditedAt(): ?DateTime
    {
        return $this->editedAt;
    }

    public function setEditedAt(?DateTime $editedAt): void
    {
        $this->editedAt = $editedAt;
    }

    public function isEdited(): bool
    {
        return $this->editedAt !== null;
    }

    abstract public function getSubjectType(): string;

    abstract public function getSubjectId(): int;
}
