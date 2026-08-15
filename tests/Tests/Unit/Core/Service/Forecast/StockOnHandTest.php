<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Service\Forecast;

use Citadel\Aureum\Core\Service\Forecast\CountObservation;
use Citadel\Aureum\Core\Service\Forecast\InventoryForecastService;
use Citadel\Aureum\Core\Service\Forecast\MovementObservation;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StockOnHandTest extends TestCase
{
    private function at(string $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date);
    }

    public function testLatestCountIsTheAnchor(): void
    {
        $stock = InventoryForecastService::stockOnHandFrom(
            [
                new CountObservation($this->at('2026-08-01'), 1000),
                new CountObservation($this->at('2026-08-08'), 400),
            ],
            [],
        );

        self::assertSame(400, $stock);
    }

    public function testMovementsAfterTheLatestCountAreApplied(): void
    {
        $stock = InventoryForecastService::stockOnHandFrom(
            [new CountObservation($this->at('2026-08-08'), 400)],
            [
                new MovementObservation($this->at('2026-08-10'), 500),
                new MovementObservation($this->at('2026-08-11'), -100),
            ],
        );

        self::assertSame(800, $stock);
    }

    public function testMovementsBeforeTheLatestCountAreIgnored(): void
    {
        $stock = InventoryForecastService::stockOnHandFrom(
            [new CountObservation($this->at('2026-08-08'), 400)],
            [new MovementObservation($this->at('2026-08-05'), 500)],
        );

        self::assertSame(400, $stock);
    }

    public function testMovementsAtTheExactCountInstantAreIgnored(): void
    {
        $stock = InventoryForecastService::stockOnHandFrom(
            [new CountObservation($this->at('2026-08-08 09:00:00'), 400)],
            [new MovementObservation($this->at('2026-08-08 09:00:00'), 500)],
        );

        self::assertSame(400, $stock);
    }

    public function testNoCountsMeansZero(): void
    {
        self::assertSame(0, InventoryForecastService::stockOnHandFrom([], []));
    }
}
