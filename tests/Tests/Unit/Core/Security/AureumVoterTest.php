<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Security;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\HotelRole;
use Citadel\Aureum\Core\Security\AureumVoter;
use Citadel\Aureum\Core\Service\AureumService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AureumVoterTest extends TestCase
{
    private function voterFor(string $rolePermission): AureumVoter
    {
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('isModuleEnabled')->willReturn(true);

        $role = $this->createMock(HotelRole::class);
        $role->method('hasPermission')
            ->willReturnCallback(static fn (string $p): bool => $p === $rolePermission);

        $employee = $this->createMock(Employee::class);
        $employee->method('isHotelAdmin')->willReturn(false);
        $employee->method('getHotel')->willReturn($hotel);
        $employee->method('getHotelRoles')->willReturn(new ArrayCollection([$role]));

        $service = $this->createMock(AureumService::class);
        $service->method('getEmployee')->willReturn($employee);

        return new AureumVoter($service);
    }

    private function vote(AureumVoter $voter, string $attribute): bool
    {
        $token = $this->createMock(TokenInterface::class);

        return $voter->vote($token, null, [$attribute]) === VoterInterface::ACCESS_GRANTED;
    }

    public function testManageGrantsCountAndView(): void
    {
        $voter = $this->voterFor('inventory.manage');

        self::assertTrue($this->vote($voter, 'aureum.module.inventory.manage'));
        self::assertTrue($this->vote($voter, 'aureum.module.inventory.count'));
        self::assertTrue($this->vote($voter, 'aureum.module.inventory.view'));
    }

    public function testCountGrantsViewButNotManage(): void
    {
        $voter = $this->voterFor('inventory.count');

        self::assertFalse($this->vote($voter, 'aureum.module.inventory.manage'));
        self::assertTrue($this->vote($voter, 'aureum.module.inventory.count'));
        self::assertTrue($this->vote($voter, 'aureum.module.inventory.view'));
    }

    public function testViewGrantsNeitherCountNorManage(): void
    {
        $voter = $this->voterFor('inventory.view');

        self::assertFalse($this->vote($voter, 'aureum.module.inventory.manage'));
        self::assertFalse($this->vote($voter, 'aureum.module.inventory.count'));
        self::assertTrue($this->vote($voter, 'aureum.module.inventory.view'));
    }

    public function testCountIsRejectedOnModulesThatDoNotDeclareIt(): void
    {
        $voter = $this->voterFor('packages.manage');

        self::assertFalse($this->vote($voter, 'aureum.module.packages.count'));
        self::assertTrue($this->vote($voter, 'aureum.module.packages.view'));
    }

    public function testExistingManageStillImpliesViewOnOtherModules(): void
    {
        $voter = $this->voterFor('lost_property.manage');

        self::assertTrue($this->vote($voter, 'aureum.module.lost_property.view'));
        self::assertTrue($this->vote($voter, 'aureum.module.lost_property.manage'));
    }

    public function testUnknownModuleIsRejected(): void
    {
        $voter = $this->voterFor('inventory.manage');

        self::assertFalse($this->vote($voter, 'aureum.module.nonsense.view'));
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->createMock(TokenInterface::class), null, ['some.other.attribute']),
        );
    }
}
