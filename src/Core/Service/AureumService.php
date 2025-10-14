<?php

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Symfony\Bundle\SecurityBundle\Security;

class AureumService
{
    public function __construct(
        private readonly Security $security,
        private readonly EmployeeRepository $employeeRepository,
    ){
    }
    public function isEmployee(): bool
    {
        $user = $this->security->getUser() ?? null;
        $employee = $this->employeeRepository->findOneBy(['user' => $user]);

        if($employee === null){
            return false;
        }

        return true;
    }
}