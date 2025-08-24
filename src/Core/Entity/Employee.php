<?php

namespace Citadel\Aureum\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\User;


#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'aureum_employees')]
class Employee
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $role;

    #[ORM\OneToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Hotel $hotel;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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