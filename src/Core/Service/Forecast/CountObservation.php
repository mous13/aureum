<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Forecast;

use DateTimeImmutable;

final readonly class CountObservation
{
    public function __construct(
        public DateTimeImmutable $at,
        public int $quantity,
    ) {
    }
}
