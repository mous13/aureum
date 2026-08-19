<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\LogEntityTrait;
use Citadel\Aureum\Core\Repository\LostPropertyLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: LostPropertyLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_lost_property')]
#[ORM\Index(columns: ['hotel_id', 'created_at'])]
#[ORM\Index(columns: ['lost_property_id', 'created_at'])]
class LostPropertyLog implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;
    use LogEntityTrait;

    #[ORM\ManyToOne(targetEntity: LostProperty::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private LostProperty $lostProperty;

    public function getLostProperty(): LostProperty
    {
        return $this->lostProperty;
    }

    public function setLostProperty(LostProperty $lostProperty): void
    {
        $this->lostProperty = $lostProperty;
    }

    public function getEntityType(): string
    {
        return 'lost_property';
    }

    public function getEntityId(): int
    {
        return $this->lostProperty->getId();
    }
}
