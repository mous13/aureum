<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Service\OpeningTimesStatus;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class OpeningTimesStatusTest extends TestCase
{
    private OpeningTimesStatus $status;

    protected function setUp(): void
    {
        $this->status = new OpeningTimesStatus();
    }

    private function week(array $overrides = []): array
    {
        $week = [];
        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
            $week[$day] = ['closed' => true, 'ranges' => []];
        }

        return array_merge($week, $overrides);
    }

    private function at(string $datetime): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime, new DateTimeZone('UTC'));
    }

    public function testUnknownWhenMissingData(): void
    {
        self::assertNull($this->status->isOpenNow(null, 'Europe/London', $this->at('2026-09-07 12:00')));
        self::assertNull($this->status->isOpenNow($this->week(), null, $this->at('2026-09-07 12:00')));
        self::assertNull($this->status->isOpenNow($this->week(), 'Not/AZone', $this->at('2026-09-07 12:00')));
    }

    public function testOpenDuringRange(): void
    {
        $times = $this->week(['mon' => ['closed' => false, 'ranges' => [['12:00', '14:30']]]]);

        self::assertTrue($this->status->isOpenNow($times, 'UTC', $this->at('2026-09-07 13:00')));
        self::assertFalse($this->status->isOpenNow($times, 'UTC', $this->at('2026-09-07 15:00')));
    }

    public function testTimezoneShiftsTheDay(): void
    {
        $times = $this->week(['mon' => ['closed' => false, 'ranges' => [['09:00', '10:00']]]]);

        self::assertTrue($this->status->isOpenNow($times, 'Asia/Tokyo', $this->at('2026-09-07 00:30')));
        self::assertFalse($this->status->isOpenNow($times, 'UTC', $this->at('2026-09-07 00:30')));
    }

    public function testMidnightCrossingOpenAfterMidnight(): void
    {
        $times = $this->week(['mon' => ['closed' => false, 'ranges' => [['18:00', '01:00']]]]);

        self::assertTrue($this->status->isOpenNow($times, 'UTC', $this->at('2026-09-07 23:00')));
        self::assertTrue($this->status->isOpenNow($times, 'UTC', $this->at('2026-09-08 00:30')));
        self::assertFalse($this->status->isOpenNow($times, 'UTC', $this->at('2026-09-08 02:00')));
    }

    public function testAlwaysOpenRange(): void
    {
        $times = $this->week(['mon' => ['closed' => false, 'ranges' => [['00:00', '00:00']]]]);

        self::assertTrue($this->status->isOpenNow($times, 'UTC', $this->at('2026-09-07 23:59')));
    }

    public function testClosedDay(): void
    {
        self::assertFalse($this->status->isOpenNow($this->week(), 'UTC', $this->at('2026-09-07 12:00')));
    }

    public function testRankOrdersOpenClosedUnknown(): void
    {
        $open = $this->week(['mon' => ['closed' => false, 'ranges' => [['09:00', '17:00']]]]);

        self::assertSame(0, $this->status->rank($open, 'UTC', $this->at('2026-09-07 12:00')));
        self::assertSame(1, $this->status->rank($this->week(), 'UTC', $this->at('2026-09-07 12:00')));
        self::assertSame(2, $this->status->rank(null, 'UTC', $this->at('2026-09-07 12:00')));
        self::assertSame(2, $this->status->rank($open, null, $this->at('2026-09-07 12:00')));
    }
}
