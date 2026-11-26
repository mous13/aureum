<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

final readonly class StreetleMapData
{
    /**
     * @param list<StreetData> $streets
     * @param array<string, list<mixed>> $basemap
     */
    public function __construct(
        public array $streets,
        public array $basemap,
    ) {
    }
}
