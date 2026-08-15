<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\Table(name: 'aureum_inventory_items')]
#[ORM\Index(columns: ['category_id'])]
#[ORM\Index(columns: ['location_id'])]
class InventoryItem
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: InventoryCategory::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private InventoryCategory $category;

    #[ORM\ManyToOne(targetEntity: StorageLocation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private StorageLocation $location;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    private string $unit;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $packSize = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $packLabel = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $leadTimeDays = null;

    #[ORM\Column(type: 'integer')]
    private int $safetyBufferDays = 7;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    public function getCategory(): InventoryCategory
    {
        return $this->category;
    }

    public function setCategory(InventoryCategory $category): void
    {
        $this->category = $category;
    }

    public function getLocation(): StorageLocation
    {
        return $this->location;
    }

    public function setLocation(StorageLocation $location): void
    {
        $this->location = $location;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    public function getPackSize(): ?int
    {
        return $this->packSize;
    }

    public function setPackSize(?int $packSize): void
    {
        $this->packSize = $packSize;
    }

    public function getPackLabel(): ?string
    {
        return $this->packLabel;
    }

    public function setPackLabel(?string $packLabel): void
    {
        $this->packLabel = $packLabel;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    public function setLeadTimeDays(?int $leadTimeDays): void
    {
        $this->leadTimeDays = $leadTimeDays;
    }

    public function getSafetyBufferDays(): int
    {
        return $this->safetyBufferDays;
    }

    public function setSafetyBufferDays(int $safetyBufferDays): void
    {
        $this->safetyBufferDays = $safetyBufferDays;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function packsFor(int $baseUnits): int
    {
        if ($baseUnits <= 0) {
            return 0;
        }

        if ($this->packSize === null || $this->packSize < 1) {
            return $baseUnits;
        }

        return (int)ceil($baseUnits / $this->packSize);
    }

    public function describeQuantity(int $baseUnits): string
    {
        if ($this->packSize === null || $this->packSize < 1) {
            return "{$baseUnits} {$this->unit}";
        }

        $packs = intdiv($baseUnits, $this->packSize);
        $loose = $baseUnits % $this->packSize;

        $parts = [];
        if ($packs > 0) {
            $parts[] = "{$packs} {$this->packLabel}";
        }

        if ($loose > 0 || $parts === []) {
            $parts[] = "{$loose} {$this->unit}";
        }

        return implode(', ', $parts);
    }
}
