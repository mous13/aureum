<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Service\Forecast;

use Citadel\Aureum\Core\Service\Forecast\ConsumptionCalculator;
use Citadel\Aureum\Core\Service\Forecast\CountObservation;
use Citadel\Aureum\Core\Service\Forecast\MovementObservation;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ConsumptionCalculatorTest extends TestCase
{
    private ConsumptionCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ConsumptionCalculator();
    }

    private function at(string $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date);
    }

    public function testSimpleDropIsConsumption(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-01'), 1000),
                new CountObservation($this->at('2026-08-08'), 300),
            ],
            [],
        );

        self::assertCount(1, $intervals);
        self::assertSame(700, $intervals[0]->consumption);
        self::assertSame(7.0, $intervals[0]->days);
        self::assertFalse($intervals[0]->excluded);
    }

    public function testDeliveryAndTransferWorkedExample(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-01'), 1000),
                new CountObservation($this->at('2026-08-08'), 1100),
            ],
            [
                new MovementObservation($this->at('2026-08-03'), 500),
                new MovementObservation($this->at('2026-08-05'), -300),
            ],
        );

        self::assertSame(400, $intervals[0]->consumption);
        self::assertFalse($intervals[0]->excluded);
    }

    public function testOutwardMovementsDoNotDoubleCount(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-01'), 1000),
                new CountObservation($this->at('2026-08-08'), 700),
            ],
            [new MovementObservation($this->at('2026-08-04'), -300)],
        );

        self::assertSame(300, $intervals[0]->consumption);
    }

    public function testUnexplainedIncreaseIsExcluded(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-01'), 1000),
                new CountObservation($this->at('2026-08-08'), 1500),
            ],
            [],
        );

        self::assertTrue($intervals[0]->excluded);
        self::assertSame(-500, $intervals[0]->consumption);
    }

    public function testMovementsOnTheBoundaryBelongToTheClosingInterval(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-01 00:00:00'), 1000),
                new CountObservation($this->at('2026-08-08 00:00:00'), 1200),
                new CountObservation($this->at('2026-08-15 00:00:00'), 900),
            ],
            [new MovementObservation($this->at('2026-08-08 00:00:00'), 500)],
        );

        self::assertSame(300, $intervals[0]->consumption);
        self::assertSame(300, $intervals[1]->consumption);
    }

    public function testCountsAreSortedBeforePairing(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-08'), 300),
                new CountObservation($this->at('2026-08-01'), 1000),
            ],
            [],
        );

        self::assertCount(1, $intervals);
        self::assertSame(700, $intervals[0]->consumption);
    }

    public function testSingleCountProducesNoIntervals(): void
    {
        $intervals = $this->calculator->intervals(
            [new CountObservation($this->at('2026-08-01'), 1000)],
            [],
        );

        self::assertSame([], $intervals);
    }

    public function testSimultaneousCountsAreSkipped(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-01 09:00:00'), 1000),
                new CountObservation($this->at('2026-08-01 09:00:00'), 900),
            ],
            [],
        );

        self::assertSame([], $intervals);
    }

    public function testFractionalDays(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-01 00:00:00'), 100),
                new CountObservation($this->at('2026-08-01 12:00:00'), 40),
            ],
            [],
        );

        self::assertSame(0.5, $intervals[0]->days);
    }

    public function testConsecutiveIntervalsChain(): void
    {
        $intervals = $this->calculator->intervals(
            [
                new CountObservation($this->at('2026-08-01'), 1000),
                new CountObservation($this->at('2026-08-08'), 300),
                new CountObservation($this->at('2026-08-15'), 100),
            ],
            [],
        );

        self::assertCount(2, $intervals);
        self::assertSame(700, $intervals[0]->consumption);
        self::assertSame(200, $intervals[1]->consumption);
    }
}
