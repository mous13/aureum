<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\MovementDirection;
use Citadel\Aureum\Core\Entity\Enum\MovementReason;
use Citadel\Aureum\Core\Repository\StockMovementRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: StockMovementRepository::class)]
#[ORM\Table(name: 'aureum_stock_movements')]
#[ORM\Index(columns: ['item_id', 'occurred_at'])]
#[ORM\Index(columns: ['hotel_id', 'occurred_at'])]
class StockMovement
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: InventoryItem::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private InventoryItem $item;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $recordedBy;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\ManyToOne(targetEntity: StorageLocation::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?StorageLocation $destination = null;

    #[ORM\Column(type: 'string', length: 16, enumType: MovementDirection::class)]
    private MovementDirection $direction;

    #[ORM\Column(type: 'string', length: 32, enumType: MovementReason::class)]
    private MovementReason $reason;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'datetime')]
    private DateTime $occurredAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->occurredAt = new DateTime();
    }

    public function getItem(): InventoryItem
    {
        return $this->item;
    }

    public function setItem(InventoryItem $item): void
    {
        $this->item = $item;
    }

    public function getRecordedBy(): Employee
    {
        return $this->recordedBy;
    }

    public function setRecordedBy(Employee $recordedBy): void
    {
        $this->recordedBy = $recordedBy;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getDestination(): ?StorageLocation
    {
        return $this->destination;
    }

    public function setDestination(?StorageLocation $destination): void
    {
        $this->destination = $destination;
    }

    public function getDirection(): MovementDirection
    {
        return $this->direction;
    }

    public function setDirection(MovementDirection $direction): void
    {
        $this->direction = $direction;
    }

    public function getReason(): MovementReason
    {
        return $this->reason;
    }

    public function setReason(MovementReason $reason): void
    {
        $this->reason = $reason;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getOccurredAt(): DateTime
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(DateTime $occurredAt): void
    {
        $this->occurredAt = $occurredAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getSignedQuantity(): int
    {
        return $this->quantity * $this->direction->sign();
    }
}
