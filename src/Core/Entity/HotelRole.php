<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\HotelRoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: HotelRoleRepository::class)]
#[ORM\Table(name: 'aureum_hotel_roles')]
#[ORM\UniqueConstraint(name: 'uniq_hotel_role_name', columns: ['hotel_id', 'name'])]
class HotelRole
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 100)]
    private string $name;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: 'json')]
    private array $permissions = [];

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'roles')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Hotel $hotel;

    /** @var Collection<int, Employee> */
    #[ORM\ManyToMany(targetEntity: Employee::class, inversedBy: 'hotelRoles')]
    #[ORM\JoinTable(name: 'aureum_hotel_role_employees')]
    private Collection $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array<string>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param array<string> $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = array_values(array_unique($permissions));
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
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
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
        }
    }

    public function removeEmployee(Employee $employee): void
    {
        $this->employees->removeElement($employee);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
