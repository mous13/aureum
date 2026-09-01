<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: HotelRepository::class)]
#[ORM\Table(name: 'aureum_hotels')]
class Hotel
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 20, unique: true)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime')]
    private DateTime $joinDate;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: Employee::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private Collection $employees;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: Package::class, cascade: ['persist', 'remove'])]
    private Collection $packages;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: Fine::class, cascade: ['persist', 'remove'])]
    private Collection $fines;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: Restaurant::class, cascade: ['persist', 'remove'])]
    private Collection $restaurants;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: Event::class, cascade: ['persist', 'remove'])]
    private Collection $events;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: Floor::class, cascade: ['persist', 'remove'])]
    private Collection $floors;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: RoomType::class, cascade: ['persist', 'remove'])]
    private Collection $roomTypes;

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: HotelRole::class, cascade: ['persist', 'remove'])]
    private Collection $roles;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: 'json')]
    private array $enabledModules = [];

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $timezone = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $googlePlacesEnabled = false;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->floors = new ArrayCollection();
        $this->roomTypes = new ArrayCollection();
        $this->employees = new ArrayCollection();
        $this->joinDate = new DateTime();
        $this->packages = new ArrayCollection();
        $this->fines = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->restaurants = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

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

    public function getJoinDate(): DateTime
    {
        return $this->joinDate;
    }

    public function setJoinDate(DateTime $joinDate): void
    {
        $this->joinDate = $joinDate;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * @return Collection<int, Employee>
     */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(Employee $employee): void
    {
        if ($this->employees->contains($employee)) {
            return;
        }

        $this->employees->add($employee);
        $employee->setHotel($this);
    }

    public function getPackages(): Collection
    {
        return $this->packages;
    }

    public function setPackages(Collection $packages): void
    {
        $this->packages = $packages;
    }

    public function getFines(): Collection
    {
        return $this->fines;
    }

    public function setFines(Collection $fines): void
    {
        $this->fines = $fines;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function getRestaurants(): Collection
    {
        return $this->restaurants;
    }

    public function setRestaurants(Collection $restaurants): void
    {
        $this->restaurants = $restaurants;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function setEvents(Collection $events): void
    {
        $this->events = $events;
    }

    /**
     * @return Collection<int, Floor>
     */
    public function getFloors(): Collection
    {
        return $this->floors;
    }

    public function setFloors(Collection $floors): void
    {
        $this->floors = $floors;
    }

    /**
     * @return Collection<int, RoomType>
     */
    public function getRoomTypes(): Collection
    {
        return $this->roomTypes;
    }

    public function setRoomTypes(Collection $roomTypes): void
    {
        $this->roomTypes = $roomTypes;
    }

    /**
     * @return Collection<int, HotelRole>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function setRoles(Collection $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @return array<string>
     */
    public function getEnabledModules(): array
    {
        return $this->enabledModules;
    }

    /**
     * @param array<string> $enabledModules
     */
    public function setEnabledModules(array $enabledModules): void
    {
        $this->enabledModules = array_values(array_unique($enabledModules));
    }

    public function isModuleEnabled(Enum\Module $module): bool
    {
        return in_array($module->value, $this->enabledModules, true);
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function isGooglePlacesEnabled(): bool
    {
        return $this->googlePlacesEnabled;
    }

    public function setGooglePlacesEnabled(bool $googlePlacesEnabled): void
    {
        $this->googlePlacesEnabled = $googlePlacesEnabled;
    }
}
