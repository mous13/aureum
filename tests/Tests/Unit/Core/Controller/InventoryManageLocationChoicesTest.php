<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Controller;

use Citadel\Aureum\Core\Controller\Manage\ItemController;
use Citadel\Aureum\Core\Entity\StorageLocation;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class InventoryManageLocationChoicesTest extends TestCase
{
    private function location(int $id): StorageLocation
    {
        $location = new StorageLocation();
        $location->setName('Location ' . $id);

        $property = new ReflectionProperty($location, 'id');
        $property->setAccessible(true);
        $property->setValue($location, $id);

        return $location;
    }

    public function testCurrentLocationIsAppendedWhenNotAlreadyAChoice(): void
    {
        $bulkA = $this->location(1);
        $bulkB = $this->location(2);
        $working = $this->location(3);

        $result = ItemController::locationChoicesWithCurrent([$bulkA, $bulkB], $working);

        self::assertSame([$bulkA, $bulkB, $working], $result);
    }

    public function testChoicesAreUnchangedWhenCurrentLocationIsAlreadyAChoice(): void
    {
        $bulkA = $this->location(1);
        $bulkB = $this->location(2);

        $result = ItemController::locationChoicesWithCurrent([$bulkA, $bulkB], $bulkB);

        self::assertSame([$bulkA, $bulkB], $result);
    }

    public function testEmptyBulkListStillIncludesTheCurrentLocation(): void
    {
        $working = $this->location(5);

        $result = ItemController::locationChoicesWithCurrent([], $working);

        self::assertSame([$working], $result);
    }
}
