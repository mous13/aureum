<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\StorageLocationType as LocationTypeEnum;
use Citadel\Aureum\Core\Form\StorageLocationType;
use PHPUnit\Framework\TestCase;

class StorageLocationTypeTest extends TestCase
{
    public function testBothTypesAreOffered(): void
    {
        self::assertSame(
            [
                'Bulk Store' => LocationTypeEnum::BULK,
                'Working Location' => LocationTypeEnum::WORKING,
            ],
            StorageLocationType::typeChoices(),
        );
    }
}
