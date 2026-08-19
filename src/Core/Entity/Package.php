<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\PackageStatus;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Trait\AnonymisableEntityTrait;
use Citadel\Aureum\Core\Repository\PackageRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;

#[ORM\Entity(repositoryClass: PackageRepository::class)]
#[ORM\Table(name: 'aureum_packages')]
#[ORM\Index(name: 'IDX_aureum_packages_anonymised_at', columns: ['anonymised_at'])]
class Package implements HotelOwnedInterface, AnonymisableInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;
    use AnonymisableEntityTrait;

    public static function getModule(): Module
    {
        return Module::PACKAGES;
    }

    public function getRetentionAnchor(): ?\DateTimeInterface
    {
        return $this->getCreatedAt();
    }

    public function anonymise(): void
    {
        $this->name = self::ANONYMISED_PLACEHOLDER;
        $this->note = null;
        $this->markAnonymised();
    }

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column(length: 255)]
    private string $location;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    private Employee $employee;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: 'string', length: 255, enumType: PackageStatus::class)]
    private PackageStatus $status;

    #[ORM\ManyToOne(inversedBy: 'packages')]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getStatus(): PackageStatus
    {
        return $this->status;
    }

    public function setStatus(PackageStatus $status): void
    {
        $this->status = $status;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }
}
