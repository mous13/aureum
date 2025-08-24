<?php

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

    /** @var Collection<int, Employee> */
    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: Employee::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private Collection $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
        $this->joinDate = new DateTime();
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
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
            $employee->setHotel($this);
        }
    }

    public function removeEmployee(Employee $employee): void
    {
        if ($this->employees->removeElement($employee)) {
            if ($employee->getHotel() === $this) {
                $employee->setHotel(null);
            }
        }
    }
}