<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Service;

use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Repository\InventoryItemLogRepository;
use Citadel\Aureum\Core\Service\InventoryItemLogService;
use PHPUnit\Framework\TestCase;

class InventoryItemLogServiceTest extends TestCase
{
    private InventoryItemLogService $service;

    protected function setUp(): void
    {
        $this->service = new InventoryItemLogService(
            $this->createMock(InventoryItemLogRepository::class),
        );
    }

    private function item(): InventoryItem
    {
        $item = new InventoryItem();
        $item->setName('Key Cards');
        $item->setUnit('card');
        $item->setPackSize(500);
        $item->setPackLabel('box');
        $item->setLeadTimeDays(14);
        $item->setSafetyBufferDays(7);
        $item->setActive(true);

        return $item;
    }

    public function testNoChangesWhenNothingMoved(): void
    {
        $item = $this->item();
        $original = $this->service->captureCurrentState($item);

        self::assertSame([], $this->service->detectChanges($item, $original));
    }

    public function testLeadTimeChangeIsRecordedWithOldAndNew(): void
    {
        $item = $this->item();
        $original = $this->service->captureCurrentState($item);
        $item->setLeadTimeDays(21);

        $changes = $this->service->detectChanges($item, $original);

        self::assertSame(['leadTimeDays' => ['old' => 14, 'new' => 21]], $changes);
    }

    public function testNullingPackSizeIsRecorded(): void
    {
        $item = $this->item();
        $original = $this->service->captureCurrentState($item);
        $item->setPackSize(null);

        $changes = $this->service->detectChanges($item, $original);

        self::assertSame(['packSize' => ['old' => 500, 'new' => null]], $changes);
    }

    public function testMultipleFieldsAreRecordedTogether(): void
    {
        $item = $this->item();
        $original = $this->service->captureCurrentState($item);
        $item->setName('Key Cards (New Supplier)');
        $item->setSafetyBufferDays(14);

        $changes = $this->service->detectChanges($item, $original);

        self::assertArrayHasKey('name', $changes);
        self::assertArrayHasKey('safetyBufferDays', $changes);
        self::assertCount(2, $changes);
    }
}
