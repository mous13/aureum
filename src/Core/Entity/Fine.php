<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\FineStatus;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Trait\AnonymisableEntityTrait;
use Citadel\Aureum\Core\Repository\FineRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;

#[ORM\Entity(repositoryClass: FineRepository::class)]
#[ORM\Table(name: 'aureum_fines')]
#[ORM\Index(name: 'IDX_aureum_fines_anonymised_at', columns: ['anonymised_at'])]
class Fine implements HotelOwnedInterface, AnonymisableInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;
    use AnonymisableEntityTrait;

    public static function getModule(): Module
    {
        return Module::FINES;
    }

    public function getRetentionAnchor(): ?\DateTimeInterface
    {
        return $this->getCreatedAt();
    }

    public function anonymise(): void
    {
        $this->name = self::ANONYMISED_PLACEHOLDER;
        $this->email = null;
        $this->note = null;
        $this->markAnonymised();
    }

    #[ORM\Column(length: 255)]
    private string $number;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    private Employee $createdBy;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    private Employee $updatedBy;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'fines')]
    private Hotel $hotel;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: 'string', length: 255, enumType: FineStatus::class)]
    private FineStatus $status;

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getCreatedBy(): Employee
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Employee $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getUpdatedBy(): Employee
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(Employee $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getStatus(): FineStatus
    {
        return $this->status;
    }

    public function setStatus(FineStatus $status): void
    {
        $this->status = $status;
    }
}
