<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\BookingField;
use Citadel\Aureum\Core\Entity\Enum\BookingStatus;
use Citadel\Aureum\Core\Entity\Enum\BookingType;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Trait\AnonymisableEntityTrait;
use Citadel\Aureum\Core\Repository\BookingRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'aureum_bookings')]
#[ORM\Index(name: 'idx_booking_hotel_date', columns: ['hotel_id', 'date'])]
#[ORM\Index(name: 'idx_booking_hotel_type', columns: ['hotel_id', 'type'])]
#[ORM\Index(name: 'IDX_aureum_bookings_anonymised_at', columns: ['anonymised_at'])]
class Booking implements HotelOwnedInterface, AnonymisableInterface
{
    use IdentifiableEntityTrait;
    use AnonymisableEntityTrait;

    public static function getModule(): Module
    {
        return Module::BOOKINGS;
    }

    public function getRetentionAnchor(): ?\DateTimeInterface
    {
        return $this->hasDate() ? $this->date : null;
    }

    public function anonymise(): void
    {
        $this->guest = null;
        $this->number = null;
        $this->email = null;
        $this->notes = null;
        $this->details = [];
        $this->markAnonymised();
    }

    #[ORM\Column(type: 'string', length: 255, enumType: BookingType::class)]
    private BookingType $type = BookingType::PRIVATE_TRANSFER;

    #[ORM\Column(type: 'datetime')]
    private DateTime $date;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $guest = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    private Employee $middleman;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vendor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cost = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    /**
     * @var array<string, string>
     */
    #[ORM\Column(type: 'json')]
    private array $details = [];

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(type: 'string', length: 255, enumType: BookingStatus::class)]
    private BookingStatus $status = BookingStatus::UNCONFIRMED;

    public function getType(): BookingType
    {
        return $this->type;
    }

    public function setType(BookingType $type): void
    {
        $this->type = $type;
        $this->details = $this->filterDetails($this->details);
    }

    public function hasDate(): bool
    {
        return isset($this->date);
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    public function getGuest(): ?string
    {
        return $this->guest;
    }

    public function setGuest(?string $guest): void
    {
        $this->guest = $guest;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function hasMiddleman(): bool
    {
        return isset($this->middleman);
    }

    public function getMiddleman(): Employee
    {
        return $this->middleman;
    }

    public function setMiddleman(Employee $middleman): void
    {
        $this->middleman = $middleman;
    }

    public function getVendor(): ?string
    {
        return $this->vendor;
    }

    public function setVendor(?string $vendor): void
    {
        $this->vendor = $vendor;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): void
    {
        $this->cost = $cost;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return array<string, string>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param array<string, string|null> $details
     */
    public function setDetails(array $details): void
    {
        $this->details = $this->filterDetails($details);
    }

    public function getDetail(BookingField $field): ?string
    {
        return $this->details[$field->value] ?? null;
    }

    /**
     * @return array<array{label: string, value: string}>
     */
    public function getSummary(): array
    {
        $summary = [];
        foreach ($this->type->getSummaryFields() as $field) {
            $value = $this->getDetail($field);
            if ($value === null) {
                continue;
            }

            $summary[] = ['label' => $field->getLabel(), 'value' => $value];
        }

        return $summary;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getStatus(): BookingStatus
    {
        return $this->status;
    }

    public function setStatus(BookingStatus $status): void
    {
        $this->status = $status;
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen() && isset($this->date) && $this->date < new DateTime();
    }

    /**
     * @param array<string, string|null> $details
     * @return array<string, string>
     */
    private function filterDetails(array $details): array
    {
        $allowed = array_map(static fn (BookingField $field) => $field->value, $this->type->getFields());

        $filtered = [];
        foreach ($allowed as $key) {
            $value = $details[$key] ?? null;
            $value = $value === null ? null : trim((string)$value);
            if ($value === null || $value === '') {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }
}
