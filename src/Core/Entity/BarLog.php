<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\LogEntityTrait;
use Citadel\Aureum\Core\Repository\BarLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: BarLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_bars')]
#[ORM\Index(columns: ['hotel_id', 'created_at'])]
#[ORM\Index(columns: ['bar_id', 'created_at'])]
class BarLog implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;
    use LogEntityTrait;

    #[ORM\ManyToOne(targetEntity: Bar::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Bar $bar;

    public function getBar(): Bar
    {
        return $this->bar;
    }

    public function setBar(Bar $bar): void
    {
        $this->bar = $bar;
    }

    public function getEntityType(): string
    {
        return 'bar';
    }

    public function getEntityId(): int
    {
        return $this->bar->getId();
    }
}
