<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service\Streetle;

use Citadel\Aureum\Core\Service\Streetle\LeaderboardPeriod;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LeaderboardPeriodTest extends TestCase
{
    /** @return iterable<string, array{LeaderboardPeriod, string, string, ?string}> */
    public static function starts(): iterable
    {
        yield 'today in London' => [LeaderboardPeriod::TODAY, '2026-11-26 15:00:00 UTC', 'Europe/London', '2026-11-26 00:00:00 UTC'];
        yield 'today ahead of UTC' => [LeaderboardPeriod::TODAY, '2026-11-26 23:30:00 UTC', 'Asia/Tokyo', '2026-11-26 15:00:00 UTC'];
        yield 'today in British summer time' => [LeaderboardPeriod::TODAY, '2026-07-01 12:00:00 UTC', 'Europe/London', '2026-06-30 23:00:00 UTC'];
        yield 'week from a Thursday' => [LeaderboardPeriod::WEEK, '2026-11-26 15:00:00 UTC', 'Europe/London', '2026-11-23 00:00:00 UTC'];
        yield 'week from a Sunday' => [LeaderboardPeriod::WEEK, '2026-11-29 22:00:00 UTC', 'Europe/London', '2026-11-23 00:00:00 UTC'];
        yield 'week from a Monday' => [LeaderboardPeriod::WEEK, '2026-11-23 08:00:00 UTC', 'Europe/London', '2026-11-23 00:00:00 UTC'];
        yield 'month' => [LeaderboardPeriod::MONTH, '2026-11-26 15:00:00 UTC', 'Europe/London', '2026-11-01 00:00:00 UTC'];
        yield 'all time' => [LeaderboardPeriod::ALL_TIME, '2026-11-26 15:00:00 UTC', 'Europe/London', null];
    }

    #[DataProvider('starts')]
    public function testPeriodsStartAtLocalMidnight(LeaderboardPeriod $period, string $now, string $zone, ?string $expected): void
    {
        $start = $period->startsAt(new DateTimeImmutable($now), new DateTimeZone($zone));

        self::assertSame(
            $expected,
            $start?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s \U\T\C'),
        );
    }
}
