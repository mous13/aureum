<?php

declare(strict_types=1);

namespace Tests\Tests\Unit;

use Citadel\Aureum\CitadelAureum;
use PHPUnit\Framework\TestCase;

class CitadelAureumTest extends TestCase
{
    public function testCorePermissionsIncludeInventory(): void
    {
        $permissions = (new CitadelAureum())->getPermissions();

        self::assertArrayHasKey('inventory', $permissions['core']);
        self::assertSame(['view', 'count', 'manage'], $permissions['core']['inventory']);
    }

    public function testExistingPermissionsAreUntouched(): void
    {
        $permissions = (new CitadelAureum())->getPermissions();

        self::assertSame(['view', 'manage'], $permissions['core']['concierge']);
        self::assertArrayHasKey('admin', $permissions);
    }
}
