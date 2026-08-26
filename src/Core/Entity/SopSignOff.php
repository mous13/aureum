<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\SopSignOffRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: SopSignOffRepository::class)]
#[ORM\Table(name: 'aureum_sop_sign_offs')]
#[ORM\UniqueConstraint(name: 'uniq_sop_sign_off', columns: ['sop_id', 'employee_id', 'version'])]
class SopSignOff implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Sop $sop;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Employee $employee;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\Column(type: 'datetime')]
    private DateTime $signedAt;

    public function __construct()
    {
        $this->signedAt = new DateTime();
    }

    public function getSop(): Sop
    {
        return $this->sop;
    }

    public function setSop(Sop $sop): void
    {
        $this->sop = $sop;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function getSignedAt(): DateTime
    {
        return $this->signedAt;
    }

    public function setSignedAt(DateTime $signedAt): void
    {
        $this->signedAt = $signedAt;
    }
}
