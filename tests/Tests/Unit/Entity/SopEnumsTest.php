<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Enum\SopStanding;
use Citadel\Aureum\Core\Entity\Enum\SopStatus;
use PHPUnit\Framework\TestCase;

class SopEnumsTest extends TestCase
{
    public function testSopsModuleExposesPermissionKeys(): void
    {
        self::assertContains('sops.view', Module::allPermissionKeys());
        self::assertContains('sops.manage', Module::allPermissionKeys());
        self::assertSame('aureum.module.sops.manage', Module::SOPS->permission('manage'));
    }

    public function testEveryStatusHasALabel(): void
    {
        foreach (SopStatus::cases() as $status) {
            self::assertNotSame('', $status->getLabel());
        }
    }

    public function testEveryStandingHasALabel(): void
    {
        foreach (SopStanding::cases() as $standing) {
            self::assertNotSame('', $standing->getLabel());
        }
    }

    public function testOnlyPublishedSopsAreActionable(): void
    {
        self::assertTrue(SopStatus::PUBLISHED->isActionable());
        self::assertFalse(SopStatus::DRAFT->isActionable());
        self::assertFalse(SopStatus::ARCHIVED->isActionable());
    }
}
