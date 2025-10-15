<?php

namespace Citadel\Aureum\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Citadel\Aureum\Core\Repository\LostPropertyLogRepository;
use Forumify\Core\Entity\IdentifiableEntityTrait;
#[ORM\Entity(repositoryClass: LostPropertyLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_lost_property')]
#[ORM\Index(columns: ['hotel_id', 'created_at'])]
#[ORM\Index(columns: ['lost_property_id', 'created_at'])]
class LostPropertyLog
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