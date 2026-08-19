<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Symfony\Bundle\SecurityBundle\Security;

class AureumService
{
    private ?Employee $employee = null;
    private mixed $resolvedFor = false;

    public function __construct(
        private readonly Security $security,
        private readonly EmployeeRepository $employeeRepository,
        private readonly HotelRepository $hotelRepository,
    ) {
    }

    public function getEmployee(): ?Employee
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return null;
        }

        if ($this->resolvedFor === $user) {
            return $this->employee;
        }

        $this->employee = $this->employeeRepository->findOneBy(['user' => $user, 'archivedAt' => null]);
        $this->resolvedFor = $user;

        return $this->employee;
    }

    public function isEmployee(): bool
    {
        return $this->getEmployee() !== null;
    }

    public function getHotel(): ?Hotel
    {
        $employee = $this->getEmployee();

        return $employee?->getHotel();
    }
}
