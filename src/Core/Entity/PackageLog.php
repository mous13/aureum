<?php

namespace Citadel\Aureum\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Citadel\Aureum\Core\Repository\PackageLogRepository;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: PackageLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_packages')]
#[ORM\Index(columns: ['hotel_id', 'created_at'])]
#[ORM\Index(columns: ['package_id', 'created_at'])]
class PackageLog
{
    use IdentifiableEntityTrait;
    use LogEntityTrait;

    #[ORM\ManyToOne(targetEntity: Package::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Package $package;

    public function getPackage(): Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): void
    {
        $this->package = $package;
    }

    public function getEntityType(): string
    {
        return 'package';
    }

    public function getEntityId(): int
    {
        return $this->package->getId();
    }
}