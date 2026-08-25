<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\AmenityCardStatus;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Trait\AnonymisableEntityTrait;
use Citadel\Aureum\Core\Repository\AmenityCardRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: AmenityCardRepository::class)]
#[ORM\Table(name: 'aureum_amenity_cards')]
#[ORM\Index(name: 'idx_amenity_card_board_status', columns: ['board_id', 'status'])]
#[ORM\Index(name: 'IDX_aureum_amenity_cards_anonymised_at', columns: ['anonymised_at'])]
class AmenityCard implements HotelOwnedInterface, AnonymisableInterface
{
    use IdentifiableEntityTrait;
    use AnonymisableEntityTrait;

    public static function getModule(): Module
    {
        return Module::AMENITIES;
    }

    public function getRetentionAnchor(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function anonymise(): void
    {
        $this->guestLastName = null;
        $this->markAnonymised();
    }

    #[ORM\ManyToOne(inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AmenityBoard $board;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(length: 20)]
    private string $roomNumber;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $guestLastName = null;

    #[ORM\Column(type: 'text')]
    private string $items = '';

    #[ORM\Column(type: 'string', length: 50, enumType: AmenityCardStatus::class)]
    private AmenityCardStatus $status = AmenityCardStatus::NOT_STARTED;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getBoard(): AmenityBoard
    {
        return $this->board;
    }

    public function setBoard(AmenityBoard $board): void
    {
        $this->board = $board;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getRoomNumber(): string
    {
        return $this->roomNumber;
    }

    public function setRoomNumber(string $roomNumber): void
    {
        $this->roomNumber = $roomNumber;
    }

    public function getGuestLastName(): ?string
    {
        return $this->guestLastName;
    }

    public function setGuestLastName(?string $guestLastName): void
    {
        $this->guestLastName = $guestLastName;
    }

    public function getItems(): string
    {
        return $this->items;
    }

    public function setItems(string $items): void
    {
        $this->items = $items;
    }

    public function getStatus(): AmenityCardStatus
    {
        return $this->status;
    }

    public function setStatus(AmenityCardStatus $status): void
    {
        $this->status = $status;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
