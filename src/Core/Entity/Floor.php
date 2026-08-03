<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\FloorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: FloorRepository::Class)]
#[ORM\Table(name: 'aureum_floors')]
class Floor
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'floors')]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'integer')]
    private int $gridWidth = 20;

    #[ORM\Column(type: 'integer')]
    private int $gridHeight = 12;

    #[ORM\OneToMany(mappedBy: 'floor', targetEntity: Room::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $rooms;

    #[ORM\OneToMany(mappedBy: 'floor', targetEntity: FloorFeature::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $features;

    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->features = new ArrayCollection();
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getGridWidth(): int
    {
        return $this->gridWidth;
    }

    public function setGridWidth(int $gridWidth): void
    {
        $this->gridWidth = $gridWidth;
    }

    public function getGridHeight(): int
    {
        return $this->gridHeight;
    }

    public function setGridHeight(int $gridHeight): void
    {
        $this->gridHeight = $gridHeight;
    }

    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function setRooms(Collection $rooms): void
    {
        $this->rooms = $rooms;
    }

    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function setFeatures(Collection $features): void
    {
        $this->features = $features;
    }
}
