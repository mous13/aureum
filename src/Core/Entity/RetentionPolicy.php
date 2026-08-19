<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Repository\RetentionPolicyRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: RetentionPolicyRepository::class)]
#[ORM\Table(name: 'aureum_retention_policies')]
#[ORM\UniqueConstraint(name: 'uniq_retention_hotel_module', columns: ['hotel_id', 'module'])]
class RetentionPolicy implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Hotel $hotel;

    #[ORM\Column(type: 'string', length: 50, enumType: Module::class)]
    private Module $module;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $retainForMonths = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Employee $updatedBy = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $updatedAt = null;

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getModule(): Module
    {
        return $this->module;
    }

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    public function getRetainForMonths(): ?int
    {
        return $this->retainForMonths;
    }

    public function setRetainForMonths(?int $retainForMonths): void
    {
        $this->retainForMonths = $retainForMonths;
        $this->updatedAt = new DateTime();
    }

    public function isEnforced(): bool
    {
        return $this->retainForMonths !== null && $this->retainForMonths > 0;
    }

    public function getCutoff(): ?DateTime
    {
        if (!$this->isEnforced()) {
            return null;
        }

        return (new DateTime())->modify("-{$this->retainForMonths} months");
    }

    public function getUpdatedBy(): ?Employee
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?Employee $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}
