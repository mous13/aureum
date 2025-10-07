<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Service;

use Citadel\Aureum\Admin\Form\DTO\NewEmployee;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Forumify\Core\Exception\UserAlreadyExistsException;
use Forumify\Core\Form\DTO\NewUser;
use Forumify\Core\Service\CreateUserService;
use Forumify\Core\Entity\User;

class CreateEmployeeService
{
    public function __construct(
        private readonly CreateUserService $createUserService,
        private readonly EmployeeRepository $employeeRepository,
    ) {
    }

    /**
     * Creates a new user and associated employee in a two-step process
     *
     * @throws UserAlreadyExistsException if username or email already exists
     * @throws \InvalidArgumentException if required data is missing
     */
    public function createEmployee(NewEmployee $newEmployeeData): Employee
    {
        $this->validateEmployeeData($newEmployeeData);

        $user = $this->createUserFromEmployeeData($newEmployeeData);

        $employee = $this->createEmployeeRecord($newEmployeeData, $user);

        return $employee;
    }

    private function validateEmployeeData(NewEmployee $data): void
    {
        if (empty($data->getUsername())) {
            throw new \InvalidArgumentException('Username is required');
        }

        if (empty($data->getEmail())) {
            throw new \InvalidArgumentException('Email is required');
        }

        if (empty($data->getPassword())) {
            throw new \InvalidArgumentException('Password is required');
        }

        if (empty($data->getName())) {
            throw new \InvalidArgumentException('Employee name is required');
        }

        if (empty($data->getRole())) {
            throw new \InvalidArgumentException('Employee role is required');
        }

        if ($data->getHotel() === null) {
            throw new \InvalidArgumentException('Hotel is required');
        }
    }

    private function createUserFromEmployeeData(NewEmployee $data): User
    {
        $newUser = new NewUser();
        $newUser->setUsername($data->getUsername());
        $newUser->setEmail($data->getEmail());
        $newUser->setPassword($data->getPassword());
        $newUser->setTimezone($data->getTimezone());

        return $this->createUserService->createUser($newUser);
    }

    private function createEmployeeRecord(NewEmployee $data, User $user): Employee
    {
        $employee = new Employee();
        $employee->setName($data->getName());
        $employee->setRole($data->getRole());
        $employee->setUser($user);
        $employee->setHotel($data->getHotel());

        $this->employeeRepository->save($employee);

        return $employee;
    }
}