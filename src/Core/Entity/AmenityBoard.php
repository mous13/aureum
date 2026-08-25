<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\AmenityBoardRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: AmenityBoardRepository::class)]
#[ORM\Table(name: 'aureum_amenity_boards')]
#[ORM\UniqueConstraint(name: 'uniq_amenity_board_hotel_date', columns: ['hotel_id', 'date'])]
class AmenityBoard implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(type: 'date')]
    private DateTime $date;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $createdBy;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    /** @var Collection<int, AmenityCard> */
    #[ORM\OneToMany(mappedBy: 'board', targetEntity: AmenityCard::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
        $this->createdAt = new DateTime();
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    public function getCreatedBy(): Employee
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Employee $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /** @return Collection<int, AmenityCard> */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(AmenityCard $card): void
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setBoard($this);
        }
    }
}
