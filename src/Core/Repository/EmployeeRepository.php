<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Exception;
use Citadel\Aureum\Core\Entity\Employee;

/**
 * @extends AbstractRepository<Employee>
 */
class EmployeeRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Employee::class;
    }
}