<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Entity\Enum;

use Citadel\Aureum\Core\Entity\Enum\Module;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testMostModulesHaveViewAndManage(): void
    {
        self::assertSame(['view', 'manage'], Module::PACKAGES->permissions());
    }

    public function testInventoryAddsCount(): void
    {
        self::assertSame(['view', 'count', 'manage'], Module::INVENTORY->permissions());
    }

    public function testExistingModuleKeysAreUnchanged(): void
    {
        $keys = Module::allPermissionKeys();

        self::assertContains('packages.view', $keys);
        self::assertContains('packages.manage', $keys);
        self::assertNotContains('packages.count', $keys);
    }

    public function testInventoryKeysIncludeCount(): void
    {
        $keys = Module::allPermissionKeys();

        self::assertContains('inventory.view', $keys);
        self::assertContains('inventory.count', $keys);
        self::assertContains('inventory.manage', $keys);
    }
}
