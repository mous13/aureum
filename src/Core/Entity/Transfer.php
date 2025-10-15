<?php

namespace Citadel\Aureum\Core\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Citadel\Aureum\Core\Repository\TransferRepository;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: TransferRepository::class)]
#[ORM\Table(name: 'aureum_transfers')]
class Transfer
{
    use IdentifiableEntityTrait;

    #[ORM\Column(type: 'datetime')]
    private DateTime $date;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $guest = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $number = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pickup= null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dropoff= null;
    #[ORM\ManyToOne(targetEntity: Employee::class)]
    private Employee $middleman;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $driver= null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cost = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;
    #[ORM\ManyToOne(inversedBy: 'transfers')]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(type: 'string', length: 255, enumType: TransferStatus::class)]
    private TransferStatus $status;

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

    public function getMiddleman(): Employee
    {
        return $this->middleman;
    }

    public function setMiddleman(Employee $middleman): void
    {
        $this->middleman = $middleman;
    }

    public function getDriver(): ?string
    {
        return $this->driver;
    }

    public function setDriver(?string $driver): void
    {
        $this->driver = $driver;
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

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getStatus(): TransferStatus
    {
        return $this->status;
    }

    public function setStatus(TransferStatus $status): void
    {
        $this->status = $status;
    }

    public function getPickup(): ?string
    {
        return $this->pickup;
    }

    public function setPickup(?string $pickup): void
    {
        $this->pickup = $pickup;
    }

    public function getDropoff(): ?string
    {
        return $this->dropoff;
    }

    public function setDropoff(?string $dropoff): void
    {
        $this->dropoff = $dropoff;
    }
}