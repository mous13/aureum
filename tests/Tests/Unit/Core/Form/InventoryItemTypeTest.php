<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Form;

use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Repository\InventoryItemLogRepository;
use Citadel\Aureum\Core\Service\InventoryItemLogService;
use PHPUnit\Framework\TestCase;

class InventoryItemTypeTest extends TestCase
{
    public function testEditingALeadTimeIsAuditable(): void
    {
        $service = new InventoryItemLogService($this->createMock(InventoryItemLogRepository::class));

        $item = new InventoryItem();
        $item->setName('Key Cards');
        $item->setUnit('card');
        $item->setLeadTimeDays(null);
        $item->setSafetyBufferDays(7);

        $before = $service->captureCurrentState($item);
        $item->setLeadTimeDays(14);

        self::assertSame(
            ['leadTimeDays' => ['old' => null, 'new' => 14]],
            $service->detectChanges($item, $before),
        );
    }
}
