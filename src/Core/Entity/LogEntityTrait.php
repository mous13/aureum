<?php

namespace Citadel\Aureum\Core\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
trait LogEntityTrait
{
    #[ORM\Column(type: 'string', length: 255, enumType: LogAction::class)]
    private LogAction $action;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $performedBy;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $changes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getAction(): LogAction
    {
        return $this->action;
    }

    public function setAction(LogAction $action): void
    {
        $this->action = $action;
    }

    public function getPerformedBy(): Employee
    {
        return $this->performedBy;
    }

    public function setPerformedBy(Employee $performedBy): void
    {
        $this->performedBy = $performedBy;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getChanges(): ?array
    {
        return $this->changes;
    }

    public function setChanges(?array $changes): void
    {
        $this->changes = $changes;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    abstract public function getEntityType(): string;

    abstract public function getEntityId(): int;
}