<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleSession;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;

class StreetleLeaderboard
{
    public function __construct(
        private readonly StreetleSessionRepository $sessionRepository,
    ) {
    }

    /** @return list<LeaderboardEntry> */
    public function forHotel(Hotel $hotel, StreetleMode $mode): array
    {
        return self::rank($this->sessionRepository->findScoredForBoard($hotel, $mode), $mode);
    }

    /**
     * @param list<StreetleSession> $sessions
     * @return list<LeaderboardEntry>
     */
    public static function rank(array $sessions, StreetleMode $mode): array
    {
        $compare = static fn (StreetleSession $a, StreetleSession $b): int => self::compare($a, $b, $mode);
        usort($sessions, static fn (StreetleSession $a, StreetleSession $b): int => $compare($a, $b)
            ?: $a->getStartedAt() <=> $b->getStartedAt());

        $entries = [];
        $seen = [];
        $previous = null;
        foreach ($sessions as $session) {
            $employee = $session->getEmployee();
            if (isset($seen[$employee->getId()])) {
                continue;
            }
            $seen[$employee->getId()] = true;

            $rank = $previous !== null && $compare($previous->session, $session) === 0
                ? $previous->rank
                : count($entries) + 1;
            $previous = new LeaderboardEntry($rank, $employee->getId(), $employee->getName(), $session);
            $entries[] = $previous;
        }

        return $entries;
    }

    /**
     * @param list<LeaderboardEntry> $entries
     */
    public static function entryFor(array $entries, int $employeeId): ?LeaderboardEntry
    {
        foreach ($entries as $entry) {
            if ($entry->employeeId === $employeeId) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @param list<LeaderboardEntry> $entries
     */
    public static function rivalFor(array $entries, int $employeeId): ?LeaderboardEntry
    {
        $mine = self::entryFor($entries, $employeeId);
        if ($mine === null) {
            return $entries === [] ? null : $entries[count($entries) - 1];
        }

        $rival = null;
        foreach ($entries as $entry) {
            if ($entry->rank < $mine->rank) {
                $rival = $entry;
            }
        }

        return $rival;
    }

    private static function compare(StreetleSession $a, StreetleSession $b, StreetleMode $mode): int
    {
        $byCorrect = $b->getCorrectCount() <=> $a->getCorrectCount();
        if ($byCorrect !== 0) {
            return $byCorrect;
        }

        return match ($mode) {
            StreetleMode::SPRINT => ($b->getAccuracy() ?? 0.0) <=> ($a->getAccuracy() ?? 0.0),
            StreetleMode::FREE_PLAY => $a->getDurationSeconds() <=> $b->getDurationSeconds(),
        };
    }
}
