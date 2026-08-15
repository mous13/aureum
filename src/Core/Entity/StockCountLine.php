<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\StockCountLineRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: StockCountLineRepository::class)]
#[ORM\Table(name: 'aureum_stock_count_lines')]
#[ORM\UniqueConstraint(name: 'uniq_count_item', columns: ['stock_count_id', 'item_id'])]
#[ORM\Index(columns: ['item_id'])]
class StockCountLine
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: StockCount::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private StockCount $stockCount;

    #[ORM\ManyToOne(targetEntity: InventoryItem::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private InventoryItem $item;

    #[ORM\ManyToOne(targetEntity: StorageLocation::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?StorageLocation $location = null;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    public function getStockCount(): StockCount
    {
        return $this->stockCount;
    }

    public function setStockCount(StockCount $stockCount): void
    {
        $this->stockCount = $stockCount;
    }

    public function getItem(): InventoryItem
    {
        return $this->item;
    }

    public function setItem(InventoryItem $item): void
    {
        $this->item = $item;
    }

    public function getLocation(): ?StorageLocation
    {
        return $this->location;
    }

    public function setLocation(?StorageLocation $location): void
    {
        $this->location = $location;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }
}
