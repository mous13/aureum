<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\LostPropertyClass;
use Citadel\Aureum\Core\Entity\Enum\LostPropertyStatus;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Trait\AnonymisableEntityTrait;
use Citadel\Aureum\Core\Repository\LostPropertyRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;

#[ORM\Entity(repositoryClass: LostPropertyRepository::class)]
#[ORM\Table(name: 'aureum_lost_property')]
#[ORM\Index(name: 'IDX_aureum_lost_property_anonymised_at', columns: ['anonymised_at'])]
class LostProperty implements HotelOwnedInterface, AnonymisableInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;
    use AnonymisableEntityTrait;

    public static function getModule(): Module
    {
        return Module::LOST_PROPERTY;
    }

    public function getRetentionAnchor(): ?\DateTimeInterface
    {
        return $this->getCreatedAt();
    }

    public function anonymise(): void
    {
        $this->guest = null;
        $this->contact = null;
        $this->note = null;
        $this->markAnonymised();
    }


    #[ORM\Column(type: 'string', length: 255, enumType: LostPropertyClass::class)]
    private LostPropertyClass $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    #[ORM\Column(type: 'string', length: 255)]
    private string $location;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $storedLocation = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    private Employee $reportedBy;

    #[ORM\Column(type: 'string', length: 255, enumType: LostPropertyStatus::class)]
    private LostPropertyStatus $status;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    private Hotel $hotel;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $guest = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $note = null;


    public function getType(): LostPropertyClass
    {
        return $this->type;
    }

    public function setType(LostPropertyClass $type): void
    {
        $this->type = $type;
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

    public function getStoredLocation(): ?string
    {
        return $this->storedLocation;
    }

    public function setStoredLocation(?string $storedLocation): void
    {
        $this->storedLocation = $storedLocation;
    }

    public function getReportedBy(): Employee
    {
        return $this->reportedBy;
    }

    public function setReportedBy(Employee $reportedBy): void
    {
        $this->reportedBy = $reportedBy;
    }

    public function getStatus(): LostPropertyStatus
    {
        return $this->status;
    }

    public function setStatus(LostPropertyStatus $status): void
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

    public function getGuest(): ?string
    {
        return $this->guest;
    }

    public function setGuest(?string $guest): void
    {
        $this->guest = $guest;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): void
    {
        $this->contact = $contact;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }
}
