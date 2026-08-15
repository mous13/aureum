<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Forecast;

use DateTimeImmutable;

final readonly class ConsumptionInterval
{
    public function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
        public int $consumption,
        public float $days,
        public bool $excluded,
    ) {
    }
}
