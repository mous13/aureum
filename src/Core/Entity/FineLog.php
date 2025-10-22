<?php

namespace Citadel\Aureum\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Citadel\Aureum\Core\Repository\FineLogRepository;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: FineLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_fines')]
#[ORM\Index(columns: ['hotel_id', 'created_at'])]
#[ORM\Index(columns: ['fine_id', 'created_at'])]
class FineLog
{
    use IdentifiableEntityTrait;
    use LogEntityTrait;

    #[ORM\ManyToOne(targetEntity: Fine::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Fine $fine;

    public function getFine(): Fine
    {
        return $this->fine;
    }

    public function setFine(Fine $fine): void
    {
        $this->fine = $fine;
    }

    public function getEntityType(): string
    {
        return 'fine';
    }

    public function getEntityId(): int
    {
        return $this->fine->getId();
    }
}