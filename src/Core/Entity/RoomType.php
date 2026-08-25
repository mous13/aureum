<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\RoomTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: RoomTypeRepository::class)]
#[ORM\Table(name: 'aureum_room_types')]
#[ORM\UniqueConstraint(name: 'uniq_hotel_code', columns: ['hotel_id', 'code'])]
#[ORM\UniqueConstraint(name: 'uniq_hotel_name', columns: ['hotel_id', 'name'])]
class RoomType implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 20)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $name;
    
    #[ORM\Column(type: 'boolean')]
    private bool $archived = false;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'roomTypes')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Hotel $hotel;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = strtoupper($code);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): void
    {
        $this->archived = $archived;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
