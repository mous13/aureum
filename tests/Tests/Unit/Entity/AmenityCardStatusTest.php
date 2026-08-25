<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\Enum\AmenityCardStatus;
use Citadel\Aureum\Core\Entity\Enum\Module;
use PHPUnit\Framework\TestCase;

class AmenityCardStatusTest extends TestCase
{
    public function testColumnsAdvanceInWorkflowOrder(): void
    {
        self::assertSame(AmenityCardStatus::READY, AmenityCardStatus::NOT_STARTED->next());
        self::assertSame(AmenityCardStatus::IN_PROGRESS, AmenityCardStatus::READY->next());
        self::assertSame(AmenityCardStatus::COMPLETED, AmenityCardStatus::IN_PROGRESS->next());
        self::assertNull(AmenityCardStatus::COMPLETED->next());
    }

    public function testEveryStatusHasALabel(): void
    {
        foreach (AmenityCardStatus::cases() as $status) {
            self::assertNotSame('', $status->getLabel());
        }
    }

    public function testAmenitiesModuleExposesPermissionKeys(): void
    {
        self::assertContains('amenities.view', Module::allPermissionKeys());
        self::assertContains('amenities.manage', Module::allPermissionKeys());
        self::assertSame('aureum.module.amenities.view', Module::AMENITIES->permission('view'));
    }
}
