<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\DressCode;
use Citadel\Aureum\Core\Entity\Enum\EntryPolicy;
use Citadel\Aureum\Core\Entity\Trait\OpeningTimesTrait;
use Citadel\Aureum\Core\Repository\BarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;

#[ORM\Entity(repositoryClass: BarRepository::class)]
#[ORM\Table(name: 'aureum_bars')]
class Bar implements VenueInterface
{
    use IdentifiableEntityTrait;
    use OpeningTimesTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $neighbourhood;

    #[ORM\Column(type: 'string', length: 255)]
    private string $street;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'bars')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', nullable: false)]
    private Hotel $hotel;

    #[ORM\ManyToMany(targetEntity: Employee::class)]
    #[ORM\JoinTable(name: 'aureum_bar_connections')]
    private Collection $connections;

    #[ORM\ManyToMany(targetEntity: BarStyle::class)]
    #[ORM\JoinTable(name: 'aureum_bar_bar_styles')]
    private Collection $styles;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'aureum_bar_tags')]
    private Collection $tags;

    #[ORM\Column(type: 'string', length: 50, nullable: true, enumType: DressCode::class)]
    private ?DressCode $dressCode = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true, enumType: EntryPolicy::class)]
    private ?EntryPolicy $entryPolicy = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $happyHour = null;

    public function __construct()
    {
        $this->connections = new ArrayCollection();
        $this->styles = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getNeighbourhood(): string
    {
        return $this->neighbourhood;
    }

    public function setNeighbourhood(string $neighbourhood): void
    {
        $this->neighbourhood = $neighbourhood;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getConnections(): Collection
    {
        return $this->connections;
    }

    public function setConnections(Collection $connections): void
    {
        $this->connections = $connections;
    }

    public function getStyles(): Collection
    {
        return $this->styles;
    }

    public function setStyles(Collection $styles): void
    {
        $this->styles = $styles;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function setTags(Collection $tags): void
    {
        $this->tags = $tags;
    }

    public function getDressCode(): ?DressCode
    {
        return $this->dressCode;
    }

    public function setDressCode(?DressCode $dressCode): void
    {
        $this->dressCode = $dressCode;
    }

    public function getEntryPolicy(): ?EntryPolicy
    {
        return $this->entryPolicy;
    }

    public function setEntryPolicy(?EntryPolicy $entryPolicy): void
    {
        $this->entryPolicy = $entryPolicy;
    }

    public function getHappyHour(): ?string
    {
        return $this->happyHour;
    }

    public function setHappyHour(?string $happyHour): void
    {
        $this->happyHour = $happyHour;
    }
}
