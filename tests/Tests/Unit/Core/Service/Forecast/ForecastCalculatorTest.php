<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Service\Forecast;

use Citadel\Aureum\Core\Entity\Enum\ReorderStatus;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Service\Forecast\ConsumptionInterval;
use Citadel\Aureum\Core\Service\Forecast\ForecastCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ForecastCalculatorTest extends TestCase
{
    private ForecastCalculator $calculator;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->calculator = new ForecastCalculator();
        $this->now = new DateTimeImmutable('2026-08-15 00:00:00');
    }

    private function item(?int $leadTime, int $buffer = 7, ?int $packSize = 500): InventoryItem
    {
        $item = new InventoryItem();
        $item->setName('Key Cards');
        $item->setUnit('card');
        $item->setPackSize($packSize);
        $item->setPackLabel('box');
        $item->setLeadTimeDays($leadTime);
        $item->setSafetyBufferDays($buffer);

        return $item;
    }

    /**
     * @return array<ConsumptionInterval>
     */
    private function steadyIntervals(int $perWeek, int $weeks): array
    {
        $intervals = [];
        for ($i = 0; $i < $weeks; $i++) {
            $from = $this->now->modify('-' . (($weeks - $i) * 7) . ' days');
            $intervals[] = new ConsumptionInterval(
                $from,
                $from->modify('+7 days'),
                $perWeek,
                7.0,
                false,
            );
        }

        return $intervals;
    }

    public function testNullLeadTimeNeedsSetup(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(null),
            1000,
            $this->steadyIntervals(70, 6),
            $this->now,
        );

        self::assertSame(ReorderStatus::NEEDS_SETUP, $forecast->status);
        self::assertNull($forecast->orderBy);
        self::assertNull($forecast->orderQuantity);
    }

    public function testNoIntervalsMeansNoData(): void
    {
        $forecast = $this->calculator->forecast($this->item(14), 1000, [], $this->now);

        self::assertSame(ReorderStatus::NO_DATA, $forecast->status);
        self::assertNull($forecast->ratePerDay);
    }

    public function testAllIntervalsExcludedMeansNoDataAndNeedsReview(): void
    {
        $excluded = new ConsumptionInterval(
            $this->now->modify('-7 days'),
            $this->now,
            -500,
            7.0,
            true,
        );

        $forecast = $this->calculator->forecast($this->item(14), 1000, [$excluded], $this->now);

        self::assertSame(ReorderStatus::NO_DATA, $forecast->status);
        self::assertTrue($forecast->needsReview);
    }

    public function testHealthyStockIsOk(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14),
            7000,
            $this->steadyIntervals(70, 8),
            $this->now,
        );

        self::assertSame(ReorderStatus::OK, $forecast->status);
        self::assertEqualsWithDelta(10.0, $forecast->ratePerDay, 0.0001);
        self::assertEqualsWithDelta(700.0, $forecast->daysOfCover, 0.0001);
    }

    public function testOrderNowWhenCoverMatchesLeadTimePlusBuffer(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14),
            250,
            $this->steadyIntervals(70, 8),
            $this->now,
        );

        self::assertSame(ReorderStatus::ORDER_NOW, $forecast->status);
        self::assertSame('2026-08-19', $forecast->orderBy?->format('Y-m-d'));
    }

    public function testOverdueWhenOrderDateHasPassed(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14),
            50,
            $this->steadyIntervals(70, 8),
            $this->now,
        );

        self::assertSame(ReorderStatus::OVERDUE, $forecast->status);
    }

    public function testDueSoonSitsBetweenSevenAndTwentyEightDays(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14),
            350,
            $this->steadyIntervals(70, 8),
            $this->now,
        );

        self::assertSame(ReorderStatus::DUE_SOON, $forecast->status);
        self::assertSame('2026-08-29', $forecast->orderBy?->format('Y-m-d'));
    }

    public function testOrderQuantityRoundsUpToAWholePack(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14),
            250,
            $this->steadyIntervals(70, 8),
            $this->now,
        );

        self::assertSame(500, $forecast->orderQuantity);
        self::assertSame(1, $forecast->orderPacks);
    }

    public function testSmallerPacksGiveAClosertFit(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14, 7, 100),
            250,
            $this->steadyIntervals(70, 8),
            $this->now,
        );

        self::assertSame(300, $forecast->orderQuantity);
        self::assertSame(3, $forecast->orderPacks);
    }

    public function testItemWithoutPackSizeOrdersTheExactRequirement(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14, 7, null),
            250,
            $this->steadyIntervals(70, 8),
            $this->now,
        );

        self::assertSame(240, $forecast->orderQuantity);
        self::assertSame(240, $forecast->orderPacks);
    }

    public function testWellStockedItemNeedsNoOrder(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14),
            100000,
            $this->steadyIntervals(70, 8),
            $this->now,
        );

        self::assertSame(ReorderStatus::OK, $forecast->status);
        self::assertNull($forecast->orderQuantity);
    }

    public function testZeroRateGivesInfiniteCoverAndNoOrder(): void
    {
        $flat = [new ConsumptionInterval(
            $this->now->modify('-56 days'),
            $this->now,
            0,
            56.0,
            false,
        )];

        $forecast = $this->calculator->forecast($this->item(14), 400, $flat, $this->now);

        self::assertSame(ReorderStatus::OK, $forecast->status);
        self::assertSame(0.0, $forecast->ratePerDay);
        self::assertNull($forecast->daysOfCover);
        self::assertNull($forecast->orderQuantity);
    }

    public function testProvisionalBelowFourIntervals(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14),
            7000,
            $this->steadyIntervals(70, 3),
            $this->now,
        );

        self::assertTrue($forecast->provisional);
        self::assertSame(3, $forecast->usableIntervals);
    }

    public function testProvisionalBelowTwentyOneDaysOfSpan(): void
    {
        $short = [];
        for ($i = 0; $i < 5; $i++) {
            $from = $this->now->modify('-' . (5 - $i) . ' days');
            $short[] = new ConsumptionInterval($from, $from->modify('+1 day'), 10, 1.0, false);
        }

        $forecast = $this->calculator->forecast($this->item(14), 7000, $short, $this->now);

        self::assertTrue($forecast->provisional);
        self::assertEqualsWithDelta(5.0, $forecast->observedDays, 0.0001);
    }

    public function testNotProvisionalWithFourIntervalsAndEnoughSpan(): void
    {
        $forecast = $this->calculator->forecast(
            $this->item(14),
            7000,
            $this->steadyIntervals(70, 4),
            $this->now,
        );

        self::assertFalse($forecast->provisional);
    }

    public function testNegativeRateIsTreatedAsUntrustworthyRatherThanZero(): void
    {
        $impossible = [new ConsumptionInterval(
            $this->now->modify('-28 days'),
            $this->now,
            -280,
            28.0,
            false,
        )];

        $forecast = $this->calculator->forecast($this->item(14), 400, $impossible, $this->now);

        self::assertSame(ReorderStatus::NO_DATA, $forecast->status);
        self::assertTrue($forecast->needsReview);
        self::assertEqualsWithDelta(-10.0, $forecast->ratePerDay, 0.0001);
        self::assertNull($forecast->orderQuantity);
    }

    public function testNeedsReviewIsIndependentOfStatus(): void
    {
        $intervals = $this->steadyIntervals(70, 8);
        $intervals[] = new ConsumptionInterval(
            $this->now->modify('-3 days'),
            $this->now,
            -200,
            3.0,
            true,
        );

        $forecast = $this->calculator->forecast($this->item(14), 50, $intervals, $this->now);

        self::assertSame(ReorderStatus::OVERDUE, $forecast->status);
        self::assertTrue($forecast->needsReview);
    }
}
