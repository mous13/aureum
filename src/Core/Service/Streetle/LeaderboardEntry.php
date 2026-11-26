<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Entity\StreetleSession;

final readonly class LeaderboardEntry
{
    public function __construct(
        public int $rank,
        public int $employeeId,
        public string $employeeName,
        public StreetleSession $session,
    ) {
    }
}
