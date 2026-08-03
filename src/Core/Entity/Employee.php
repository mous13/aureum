<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\User;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'aureum_employees')]
class Employee
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $hotelAdmin = false;

    #[ORM\OneToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Hotel $hotel;

    /** @var Collection<int, HotelRole> */
    #[ORM\ManyToMany(targetEntity: HotelRole::class, mappedBy: 'employees')]
    private Collection $hotelRoles;

    public function __construct()
    {
        $this->hotelRoles = new ArrayCollection();
    }

    /**
     * @return Collection<int, HotelRole>
     */
    public function getHotelRoles(): Collection
    {
        return $this->hotelRoles;
    }

    public function isHotelAdmin(): bool
    {
        return $this->hotelAdmin;
    }

    public function setHotelAdmin(bool $hotelAdmin): void
    {
        $this->hotelAdmin = $hotelAdmin;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }
}
