<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Repository\AccessLogRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: AccessLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_access')]
#[ORM\Index(name: 'IDX_aureum_logs_access_hotel_at', columns: ['hotel_id', 'accessed_at'])]
#[ORM\Index(name: 'IDX_aureum_logs_access_at', columns: ['accessed_at'])]
class AccessLog implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Hotel $hotel;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Employee $employee = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $employeeName;

    #[ORM\Column(type: 'string', length: 50, enumType: Module::class)]
    private Module $module;

    #[ORM\Column(type: 'string', length: 20)]
    private string $method;

    #[ORM\Column(type: 'string', length: 255)]
    private string $path;

    #[ORM\Column(name: 'accessed_at', type: 'datetime')]
    private DateTime $accessedAt;

    public function __construct()
    {
        $this->accessedAt = new DateTime();
    }

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getEmployeeName(): string
    {
        return $this->employeeName;
    }

    public function setEmployeeName(string $employeeName): void
    {
        $this->employeeName = $employeeName;
    }

    public function getModule(): Module
    {
        return $this->module;
    }

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getAccessedAt(): DateTime
    {
        return $this->accessedAt;
    }
}
