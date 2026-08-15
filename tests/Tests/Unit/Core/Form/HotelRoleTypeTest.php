<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Form\HotelRoleType;
use PHPUnit\Framework\TestCase;

class HotelRoleTypeTest extends TestCase
{
    public function testInventoryHasThreeLevels(): void
    {
        $choices = HotelRoleType::permissionChoices();

        self::assertSame([
            'View' => 'inventory.view',
            'Count' => 'inventory.count',
            'Manage' => 'inventory.manage',
        ], $choices['Inventory']);
    }

    public function testTwoLevelModuleIsUnchanged(): void
    {
        $choices = HotelRoleType::permissionChoices();

        self::assertSame([
            'View' => 'packages.view',
            'Manage' => 'packages.manage',
        ], $choices['Packages']);
    }

    public function testEveryModuleAppearsAsAKey(): void
    {
        $choices = HotelRoleType::permissionChoices();

        foreach (Module::cases() as $module) {
            self::assertArrayHasKey($module->getLabel(), $choices);
        }
    }
}
