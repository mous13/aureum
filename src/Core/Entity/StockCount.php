<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\StockCountRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: StockCountRepository::class)]
#[ORM\Table(name: 'aureum_stock_counts')]
#[ORM\Index(columns: ['hotel_id', 'counted_at'])]
#[ORM\Index(columns: ['inventory_id', 'counted_at'])]
class StockCount
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Inventory::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Inventory $inventory;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $countedBy;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(type: 'datetime')]
    private DateTime $countedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'stockCount', targetEntity: StockCountLine::class, cascade: ['persist', 'remove'])]
    private Collection $lines;

    public function __construct()
    {
        $this->countedAt = new DateTime();
        $this->lines = new ArrayCollection();
    }

    public function getInventory(): Inventory
    {
        return $this->inventory;
    }

    public function setInventory(Inventory $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function getCountedBy(): Employee
    {
        return $this->countedBy;
    }

    public function setCountedBy(Employee $countedBy): void
    {
        $this->countedBy = $countedBy;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getCountedAt(): DateTime
    {
        return $this->countedAt;
    }

    public function setCountedAt(DateTime $countedAt): void
    {
        $this->countedAt = $countedAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(StockCountLine $line): void
    {
        if ($this->lines->contains($line)) {
            return;
        }

        $this->lines->add($line);
        $line->setStockCount($this);
    }
}
