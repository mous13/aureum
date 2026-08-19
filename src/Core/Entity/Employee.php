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
#[ORM\Index(name: 'IDX_65A90A71_archived_at', columns: ['archived_at'])]
class Employee implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $hotelAdmin = false;

    #[ORM\OneToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'archived_at', type: 'datetime', nullable: true)]
    private ?\DateTime $archivedAt = null;

    #[ORM\Column(name: 'must_change_password', type: 'boolean', options: ['default' => 0])]
    private bool $mustChangePassword = false;

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

    /** @return Collection<int, HotelRole> */
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function setMustChangePassword(bool $mustChangePassword): void
    {
        $this->mustChangePassword = $mustChangePassword;
    }

    public function getArchivedAt(): ?\DateTime
    {
        return $this->archivedAt;
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    public function archive(): void
    {
        $this->user = null;
        $this->archivedAt = new \DateTime();
        foreach ($this->hotelRoles as $role) {
            $role->removeEmployee($this);
        }
        $this->hotelRoles->clear();
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
