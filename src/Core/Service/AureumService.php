<?php

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Symfony\Bundle\SecurityBundle\Security;

class AureumService
{
    public function __construct(
        private readonly Security $security,
        private readonly EmployeeRepository $employeeRepository,
        private readonly HotelRepository $hotelRepository,
    ){
    }

    public function getEmployee(): ?Employee
    {
        $user = $this->security->getUser();

        return $this->employeeRepository->findOneBy(['user' => $user]);
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