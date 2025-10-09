<?php

namespace Citadel\Aureum\Core\Entity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Citadel\Aureum\Core\Repository\FineRepository;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;

#[ORM\Entity(repositoryClass: FineRepository::class)]
#[ORM\Table(name: 'aureum_fines')]
class Fine
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $number;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    private Employee $createdBy;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    private Employee $updatedBy;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    private Hotel $hotel;

    #[ORM\Column(length: 255)]
    private string $comment;

    #[ORM\Column(type: 'string', length: 255, enumType: FineStatus::class)]
    private FineStatus $status;

    #[ORM\Column]
    private DateTime $date;

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getCreatedBy(): Employee
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Employee $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getUpdatedBy(): Employee
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(Employee $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getStatus(): FineStatus
    {
        return $this->status;
    }

    public function setStatus(FineStatus $status): void
    {
        $this->status = $status;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }
}