<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\ScorableTrait;
use Citadel\Aureum\Core\Repository\RestaurantRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
#[ORM\Table(name: 'aureum_restaurants')]
class Restaurant implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;
    use ScorableTrait;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\ManyToMany(targetEntity: Employee::class)]
    #[ORM\JoinTable(name: 'aureum_restaurant_connections')]
    private Collection $connections;

    #[ORM\ManyToMany(targetEntity: Cuisine::class)]
    #[ORM\JoinTable(name: 'aureum_restaurant_cuisines')]
    private Collection $cuisines;

    #[ORM\Column(type: 'string', length: 255)]
    private string $neighbourhood;

    #[ORM\Column(type: 'string', length: 255)]
    private string $street;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'restaurants')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', nullable: false)]
    private Hotel $hotel;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'aureum_restaurant_tag')]
    private Collection $tags;

    #[ORM\Column(type: 'json')]
    private array $mealPeriods = [];

    #[ORM\Column(type: 'json')]
    private array $dietaryRequirements = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $score = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $openingTimes = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googlePlaceId = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $openingTimesSyncedAt = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->connections = new ArrayCollection();
        $this->cuisines = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getConnections(): Collection
    {
        return $this->connections;
    }

    public function setConnections(Collection $connections): void
    {
        $this->connections = $connections;
    }

    public function getCuisines(): Collection
    {
        return $this->cuisines;
    }

    public function setCuisines(Collection $cuisines): void
    {
        $this->cuisines = $cuisines;
    }

    public function getNeighbourhood(): string
    {
        return $this->neighbourhood;
    }

    public function setNeighbourhood(string $neighbourhood): void
    {
        $this->neighbourhood = $neighbourhood;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function setTags(Collection $tags): void
    {
        $this->tags = $tags;
    }

    public function getMealPeriods(): array
    {
        return $this->mealPeriods;
    }

    public function setMealPeriods(array $mealPeriods): void
    {
        $this->mealPeriods = $mealPeriods;
    }

    public function getDietaryRequirements(): array
    {
        return $this->dietaryRequirements;
    }

    public function setDietaryRequirements(array $dietaryRequirements): void
    {
        $this->dietaryRequirements = $dietaryRequirements;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getOpeningTimes(): ?array
    {
        return $this->openingTimes;
    }

    public function setOpeningTimes(?array $openingTimes): void
    {
        $this->openingTimes = $openingTimes;
    }

    public function getGooglePlaceId(): ?string
    {
        return $this->googlePlaceId;
    }

    public function setGooglePlaceId(?string $googlePlaceId): void
    {
        $this->googlePlaceId = $googlePlaceId;
    }

    public function getOpeningTimesSyncedAt(): ?DateTime
    {
        return $this->openingTimesSyncedAt;
    }

    public function setOpeningTimesSyncedAt(?DateTime $openingTimesSyncedAt): void
    {
        $this->openingTimesSyncedAt = $openingTimesSyncedAt;
    }
}
