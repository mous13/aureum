<?php

namespace Citadel\Aureum\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Citadel\Aureum\Core\Repository\employeeRepository;
use Forumify\Core\Entity\User;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'aureum_employees')]
class Employee
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $firstName;

    #[ORM\Column(length: 255)]
    private string $lastName;

    #[ORM\Column(length: 255)]
    private string $role;

    #[ORM\OneToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Hotel $hotel;

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
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