<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service\Streetle;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleSession;
use Citadel\Aureum\Core\Service\Streetle\LeaderboardEntry;
use Citadel\Aureum\Core\Service\Streetle\StreetleLeaderboard;
use Citadel\Aureum\Tests\Unit\EntityIdTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StreetleLeaderboardTest extends TestCase
{
    use EntityIdTrait;

    private Hotel $hotel;
    private int $nextSessionId = 1;

    protected function setUp(): void
    {
        $this->hotel = new Hotel();
    }

    public function testEachPersonAppearsOnceWithTheirBestSprint(): void
    {
        $sam = $this->employee(1, 'Sam');
        $alex = $this->employee(2, 'Alex');

        $board = StreetleLeaderboard::rank([
            $this->sprint($sam, correct: 8, guesses: 10),
            $this->sprint($sam, correct: 12, guesses: 15),
            $this->sprint($alex, correct: 10, guesses: 10),
        ], StreetleMode::SPRINT);

        self::assertSame(['Sam', 'Alex'], array_map(static fn (LeaderboardEntry $e): string => $e->employeeName, $board));
        self::assertSame(12, $board[0]->session->getCorrectCount());
        self::assertSame([1, 2], array_map(static fn (LeaderboardEntry $e): int => $e->rank, $board));
    }

    public function testSprintTiesAreBrokenByAccuracy(): void
    {
        $board = StreetleLeaderboard::rank([
            $this->sprint($this->employee(1, 'Sam'), correct: 10, guesses: 20),
            $this->sprint($this->employee(2, 'Alex'), correct: 10, guesses: 12),
        ], StreetleMode::SPRINT);

        self::assertSame('Alex', $board[0]->employeeName);
    }

    public function testFreePlayTiesAreBrokenBySpeed(): void
    {
        $board = StreetleLeaderboard::rank([
            $this->freePlay($this->employee(1, 'Sam'), correct: 30, seconds: 900),
            $this->freePlay($this->employee(2, 'Alex'), correct: 30, seconds: 600),
            $this->freePlay($this->employee(3, 'Jo'), correct: 31, seconds: 2000),
        ], StreetleMode::FREE_PLAY);

        self::assertSame(['Jo', 'Alex', 'Sam'], array_map(static fn (LeaderboardEntry $e): string => $e->employeeName, $board));
    }

    public function testIdenticalScoresShareARank(): void
    {
        $board = StreetleLeaderboard::rank([
            $this->sprint($this->employee(1, 'Sam'), correct: 10, guesses: 10),
            $this->sprint($this->employee(2, 'Alex'), correct: 10, guesses: 10),
            $this->sprint($this->employee(3, 'Jo'), correct: 5, guesses: 5),
        ], StreetleMode::SPRINT);

        self::assertSame([1, 1, 3], array_map(static fn (LeaderboardEntry $e): int => $e->rank, $board));
    }

    public function testTheRivalIsThePersonDirectlyAhead(): void
    {
        $board = StreetleLeaderboard::rank([
            $this->sprint($this->employee(1, 'Sam'), correct: 20, guesses: 20),
            $this->sprint($this->employee(2, 'Alex'), correct: 15, guesses: 15),
            $this->sprint($this->employee(3, 'Jo'), correct: 9, guesses: 9),
        ], StreetleMode::SPRINT);

        self::assertSame('Alex', StreetleLeaderboard::rivalFor($board, 3)?->employeeName);
        self::assertNull(StreetleLeaderboard::rivalFor($board, 1));
        self::assertSame('Jo', StreetleLeaderboard::rivalFor($board, 99)?->employeeName);
        self::assertSame('Alex', StreetleLeaderboard::entryFor($board, 2)?->employeeName);
        self::assertNull(StreetleLeaderboard::entryFor($board, 99));
    }

    public function testNobodyIsARivalOnAnEmptyBoard(): void
    {
        self::assertNull(StreetleLeaderboard::rivalFor([], 1));
    }

    private function employee(int $id, string $name): Employee
    {
        $employee = new Employee();
        $employee->setName($name);
        $employee->setHotel($this->hotel);

        return $this->withId($employee, $id);
    }

    private function sprint(Employee $employee, int $correct, int $guesses): StreetleSession
    {
        $start = new DateTimeImmutable('2026-11-26 10:00:00');
        $session = new StreetleSession($employee, StreetleMode::SPRINT, $start);
        for ($i = 0; $i < $guesses; $i++) {
            $i < $correct ? $session->recordCorrectGuess($i, $start) : $session->recordWrongGuess($start);
        }
        $session->end($start->modify('+300 seconds'));

        return $this->withId($session, $this->nextSessionId++);
    }

    private function freePlay(Employee $employee, int $correct, int $seconds): StreetleSession
    {
        $start = new DateTimeImmutable('2026-11-26 10:00:00');
        $session = new StreetleSession($employee, StreetleMode::FREE_PLAY, $start);
        for ($i = 0; $i < $correct; $i++) {
            $session->recordCorrectGuess($i, $start);
        }
        $session->end($start->modify("+{$seconds} seconds"));

        return $this->withId($session, $this->nextSessionId++);
    }
}
