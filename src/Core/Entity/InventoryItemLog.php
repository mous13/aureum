<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\LogEntityTrait;
use Citadel\Aureum\Core\Repository\InventoryItemLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: InventoryItemLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_inventory_items')]
#[ORM\Index(columns: ['hotel_id', 'created_at'])]
#[ORM\Index(columns: ['item_id', 'created_at'])]
class InventoryItemLog
{
    use IdentifiableEntityTrait;
    use LogEntityTrait;

    #[ORM\ManyToOne(targetEntity: InventoryItem::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private InventoryItem $item;

    public function getItem(): InventoryItem
    {
        return $this->item;
    }

    public function setItem(InventoryItem $item): void
    {
        $this->item = $item;
    }

    public function getEntityType(): string
    {
        return 'inventory_item';
    }

    public function getEntityId(): int
    {
        return $this->item->getId();
    }
}
