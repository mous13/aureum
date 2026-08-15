<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\InventoryCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: InventoryCategoryRepository::class)]
#[ORM\Table(name: 'aureum_inventory_categories')]
class InventoryCategory
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Inventory::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Inventory $inventory;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: InventoryItem::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getInventory(): Inventory
    {
        return $this->inventory;
    }

    public function setInventory(Inventory $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }
}
