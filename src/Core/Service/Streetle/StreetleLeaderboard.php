<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleSession;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;
use DateTimeImmutable;
use DateTimeZone;

class StreetleLeaderboard
{
    public function __construct(
        private readonly StreetleSessionRepository $sessionRepository,
    ) {
    }

    public const DEFAULT_TIMEZONE = 'Europe/London';

    /** @return array<value-of<LeaderboardPeriod>, list<LeaderboardEntry>> */
    public function forHotel(Hotel $hotel, StreetleMode $mode, DateTimeImmutable $now): array
    {
        return self::byPeriod(
            $this->sessionRepository->findScoredForBoard($hotel, $mode),
            $mode,
            $now,
            new DateTimeZone($hotel->getTimezone() ?? self::DEFAULT_TIMEZONE),
        );
    }

    /**
     * @param list<StreetleSession> $sessions
     * @return array<value-of<LeaderboardPeriod>, list<LeaderboardEntry>>
     */
    public static function byPeriod(array $sessions, StreetleMode $mode, DateTimeImmutable $now, DateTimeZone $zone): array
    {
        $boards = [];
        foreach (LeaderboardPeriod::cases() as $period) {
            $start = $period->startsAt($now, $zone);
            $boards[$period->value] = self::rank(
                $start === null ? $sessions : array_values(array_filter(
                    $sessions,
                    static fn (StreetleSession $session): bool => $session->getEndedAt() >= $start,
                )),
                $mode,
            );
        }

        return $boards;
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
     * @param array<value-of<LeaderboardPeriod>, list<LeaderboardEntry>> $boards
     */
    public static function widestWin(array $boards, StreetleSession $session): ?LeaderboardPeriod
    {
        foreach ([LeaderboardPeriod::ALL_TIME, LeaderboardPeriod::MONTH, LeaderboardPeriod::WEEK, LeaderboardPeriod::TODAY] as $period) {
            $leader = $boards[$period->value][0] ?? null;
            if ($leader !== null && $leader->rank === 1 && $leader->session->getId() === $session->getId()) {
                return $period;
            }
        }

        return null;
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
