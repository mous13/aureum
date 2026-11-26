<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use DateTimeImmutable;
use DateTimeZone;

enum LeaderboardPeriod: string
{
    case TODAY = 'today';
    case WEEK = 'week';
    case MONTH = 'month';
    case ALL_TIME = 'all';

    public static function fromQuery(?string $value): self
    {
        return self::tryFrom((string)$value) ?? self::WEEK;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::TODAY => 'Today',
            self::WEEK => 'This week',
            self::MONTH => 'This month',
            self::ALL_TIME => 'All time',
        };
    }

    public function getPhrase(): ?string
    {
        return match ($this) {
            self::TODAY => 'today',
            self::WEEK => 'this week',
            self::MONTH => 'this month',
            self::ALL_TIME => null,
        };
    }

    public function startsAt(DateTimeImmutable $now, DateTimeZone $zone): ?DateTimeImmutable
    {
        $local = $now->setTimezone($zone);

        return match ($this) {
            self::TODAY => $local->setTime(0, 0),
            self::WEEK => $local->modify('monday this week')->setTime(0, 0),
            self::MONTH => $local->modify('first day of this month')->setTime(0, 0),
            self::ALL_TIME => null,
        };
    }
}
