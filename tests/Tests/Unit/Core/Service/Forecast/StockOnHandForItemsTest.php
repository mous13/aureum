<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Service\Forecast;

use Citadel\Aureum\Core\Entity\Enum\MovementDirection;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StockCount;
use Citadel\Aureum\Core\Entity\StockCountLine;
use Citadel\Aureum\Core\Entity\StockMovement;
use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\StockCountLineRepository;
use Citadel\Aureum\Core\Repository\StockMovementRepository;
use Citadel\Aureum\Core\Service\Forecast\ConsumptionCalculator;
use Citadel\Aureum\Core\Service\Forecast\CountObservation;
use Citadel\Aureum\Core\Service\Forecast\ForecastCalculator;
use Citadel\Aureum\Core\Service\Forecast\InventoryForecastService;
use Citadel\Aureum\Core\Service\Forecast\MovementObservation;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class StockOnHandForItemsTest extends TestCase
{
    private function withId(object $entity, int $id): object
    {
        $property = new ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);

        return $entity;
    }

    private function item(int $id): InventoryItem
    {
        $item = new InventoryItem();
        $item->setName('Item ' . $id);
        $item->setUnit('unit');

        return $this->withId($item, $id);
    }

    private function stockCountLine(InventoryItem $item, string $countedAt, int $quantity): StockCountLine
    {
        $count = new StockCount();
        $count->setCountedAt(new DateTime($countedAt));

        $line = new StockCountLine();
        $line->setStockCount($count);
        $line->setItem($item);
        $line->setQuantity($quantity);

        return $line;
    }

    private function movement(
        InventoryItem $item,
        string $occurredAt,
        int $quantity,
        MovementDirection $direction,
    ): StockMovement {
        $movement = new StockMovement();
        $movement->setItem($item);
        $movement->setOccurredAt(new DateTime($occurredAt));
        $movement->setQuantity($quantity);
        $movement->setDirection($direction);

        return $movement;
    }

    private function service(array $countLines, array $movements): InventoryForecastService
    {
        $countLineRepository = $this->createMock(StockCountLineRepository::class);
        $countLineRepository->method('findForHotelSince')->willReturn($countLines);

        $movementRepository = $this->createMock(StockMovementRepository::class);
        $movementRepository->method('findForHotelSince')->willReturn($movements);

        return new InventoryForecastService(
            $this->createMock(InventoryItemRepository::class),
            $countLineRepository,
            $movementRepository,
            $this->createMock(ConsumptionCalculator::class),
            $this->createMock(ForecastCalculator::class),
        );
    }

    public function testBatchedResultMatchesStockOnHandFromPerItem(): void
    {
        $hotel = new Hotel();
        $itemA = $this->item(1);
        $itemB = $this->item(2);

        $service = $this->service(
            [
                $this->stockCountLine($itemA, '2026-08-01', 1000),
                $this->stockCountLine($itemA, '2026-08-08', 400),
                $this->stockCountLine($itemB, '2026-08-05', 50),
            ],
            [
                $this->movement($itemA, '2026-08-10', 500, MovementDirection::IN),
                $this->movement($itemB, '2026-08-06', 20, MovementDirection::OUT),
            ],
        );

        $result = $service->stockOnHandForItems($hotel, [$itemA, $itemB]);

        $expectedA = InventoryForecastService::stockOnHandFrom(
            [
                new CountObservation(new DateTimeImmutable('2026-08-01'), 1000),
                new CountObservation(new DateTimeImmutable('2026-08-08'), 400),
            ],
            [new MovementObservation(new DateTimeImmutable('2026-08-10'), 500)],
        );
        $expectedB = InventoryForecastService::stockOnHandFrom(
            [new CountObservation(new DateTimeImmutable('2026-08-05'), 50)],
            [new MovementObservation(new DateTimeImmutable('2026-08-06'), -20)],
        );

        self::assertSame($expectedA, $result[1]);
        self::assertSame($expectedB, $result[2]);
        self::assertSame(900, $result[1]);
        self::assertSame(30, $result[2]);
    }

    public function testObservationsDoNotLeakBetweenItems(): void
    {
        $hotel = new Hotel();
        $itemA = $this->item(1);
        $itemB = $this->item(2);

        $service = $this->service(
            [$this->stockCountLine($itemA, '2026-08-01', 1000)],
            [$this->movement($itemB, '2026-08-02', 999, MovementDirection::IN)],
        );

        $result = $service->stockOnHandForItems($hotel, [$itemA, $itemB]);

        self::assertSame(1000, $result[1]);
        self::assertSame(0, $result[2]);
    }

    public function testItemWithNoObservationsIsZero(): void
    {
        $hotel = new Hotel();
        $item = $this->item(3);

        $service = $this->service([], []);

        $result = $service->stockOnHandForItems($hotel, [$item]);

        self::assertSame(0, $result[3]);
    }
}
