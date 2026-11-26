<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Entity\Enum\StreetKind;

final readonly class StreetData
{
    /**
     * @param list<list<array{float, float}>> $lines
     */
    public function __construct(
        public string $name,
        public string $normalizedName,
        public StreetKind $kind,
        public array $lines,
    ) {
    }
}
