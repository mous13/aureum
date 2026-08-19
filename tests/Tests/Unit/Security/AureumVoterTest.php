<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Security;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\HotelRole;
use Citadel\Aureum\Core\Security\AureumVoter;
use Citadel\Aureum\Core\Service\AureumService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AureumVoterTest extends TestCase
{
    public function testDeniesEverythingWhenTheUserIsNotAnEmployee(): void
    {
        $voter = $this->voter(null);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token(), null, ['aureum.module.fines.view']),
        );
    }

    public function testHotelAdminGetsEveryEnabledModulePermission(): void
    {
        $employee = $this->employee(hotelAdmin: true, enabledModules: [Module::FINES->value]);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter($employee)->vote($this->token(), null, ['aureum.module.fines.manage']),
        );
    }

    /**
     * Disabling a module has to override role grants, otherwise turning a module
     * off would leave it reachable for anyone who already had the permission.
     */
    public function testDisabledModuleBeatsEvenHotelAdmin(): void
    {
        $employee = $this->employee(hotelAdmin: true, enabledModules: []);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($employee)->vote($this->token(), null, ['aureum.module.fines.view']),
        );
    }

    public function testManagePermissionImpliesView(): void
    {
        $employee = $this->employee(
            hotelAdmin: false,
            enabledModules: [Module::FINES->value],
            permissions: ['fines.manage'],
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter($employee)->vote($this->token(), null, ['aureum.module.fines.view']),
        );
    }

    public function testViewPermissionDoesNotImplyManage(): void
    {
        $employee = $this->employee(
            hotelAdmin: false,
            enabledModules: [Module::FINES->value],
            permissions: ['fines.view'],
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($employee)->vote($this->token(), null, ['aureum.module.fines.manage']),
        );
    }

    public function testPermissionOnOneModuleDoesNotLeakToAnother(): void
    {
        $employee = $this->employee(
            hotelAdmin: false,
            enabledModules: [Module::FINES->value, Module::BOOKINGS->value],
            permissions: ['fines.manage'],
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($employee)->vote($this->token(), null, ['aureum.module.bookings.view']),
        );
    }

    public function testRbacManageIsHotelAdminOnly(): void
    {
        $withRolePermission = $this->employee(
            hotelAdmin: false,
            enabledModules: [],
            permissions: ['rbac.manage'],
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($withRolePermission)->vote($this->token(), null, [AureumVoter::RBAC_MANAGE]),
        );
    }

    public function testEmployeeManageCanBeGrantedThroughARole(): void
    {
        $employee = $this->employee(
            hotelAdmin: false,
            enabledModules: [],
            permissions: [AureumVoter::EMPLOYEE_MANAGE_PERMISSION],
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter($employee)->vote($this->token(), null, [AureumVoter::EMPLOYEE_MANAGE]),
        );
    }

    public function testEmployeeManageIsDeniedWithoutTheRoleOrAdminFlag(): void
    {
        $employee = $this->employee(hotelAdmin: false, enabledModules: [], permissions: ['fines.manage']);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($employee)->vote($this->token(), null, [AureumVoter::EMPLOYEE_MANAGE]),
        );
    }

    public function testUnknownModuleIsDenied(): void
    {
        $employee = $this->employee(hotelAdmin: true, enabledModules: []);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($employee)->vote($this->token(), null, ['aureum.module.payroll.view']),
        );
    }

    private function voter(?Employee $employee): AureumVoter
    {
        $service = $this->createMock(AureumService::class);
        $service->method('getEmployee')->willReturn($employee);

        return new AureumVoter($service);
    }

    private function token(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    /**
     * @param array<string> $enabledModules
     * @param array<string> $permissions
     */
    private function employee(bool $hotelAdmin, array $enabledModules, array $permissions = []): Employee
    {
        $hotel = new Hotel();
        $hotel->setEnabledModules($enabledModules);

        $employee = new Employee();
        $employee->setHotelAdmin($hotelAdmin);
        $employee->setHotel($hotel);

        if ($permissions !== []) {
            $role = new HotelRole();
            $role->setPermissions($permissions);
            $role->addEmployee($employee);
            $employee->getHotelRoles()->add($role);
        }

        return $employee;
    }
}
