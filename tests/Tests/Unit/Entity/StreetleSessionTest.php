<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleSession;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StreetleSessionTest extends TestCase
{
    private DateTimeImmutable $start;

    protected function setUp(): void
    {
        $this->start = new DateTimeImmutable('2026-11-26 10:00:00');
    }

    public function testASprintHasAFiveMinuteDeadline(): void
    {
        $session = $this->session(StreetleMode::SPRINT);

        self::assertEquals(new DateTimeImmutable('2026-11-26 10:05:00'), $session->getDeadline());
        self::assertFalse($session->hasExpired(new DateTimeImmutable('2026-11-26 10:05:01')));
        self::assertTrue($session->hasExpired(new DateTimeImmutable('2026-11-26 10:05:03')));
    }

    public function testFreePlayNeverExpires(): void
    {
        $session = $this->session(StreetleMode::FREE_PLAY);

        self::assertNull($session->getDeadline());
        self::assertFalse($session->hasExpired(new DateTimeImmutable('2026-11-27 10:00:00')));
    }

    public function testGuessesAndRevealsAreCounted(): void
    {
        $session = $this->session(StreetleMode::FREE_PLAY);
        $session->recordWrongGuess($this->at(10));
        $session->recordCorrectGuess(7, $this->at(20));
        $session->recordCorrectGuess(9, $this->at(30));
        $session->reveal(12, $this->at(40));

        self::assertSame(3, $session->getGuessCount());
        self::assertSame(2, $session->getCorrectCount());
        self::assertSame([7, 9], $session->getNamedStreetIds());
        self::assertSame([12], $session->getRevealedStreetIds());
        self::assertTrue($session->isSettled(7));
        self::assertTrue($session->isSettled(12));
        self::assertFalse($session->isSettled(99));
        self::assertEqualsWithDelta(2 / 3, $session->getAccuracy(), 0.0001);
    }

    public function testAccuracyIsUnknownBeforeAnyGuess(): void
    {
        self::assertNull($this->session(StreetleMode::FREE_PLAY)->getAccuracy());
    }

    public function testEndingASprintLateIsCappedAtTheDeadline(): void
    {
        $session = $this->session(StreetleMode::SPRINT);
        $session->end(new DateTimeImmutable('2026-11-26 10:09:00'));

        self::assertSame(300, $session->getDurationSeconds());
        self::assertFalse($session->isOpen());
    }

    public function testEndingTwiceKeepsTheFirstEnd(): void
    {
        $session = $this->session(StreetleMode::FREE_PLAY);
        $session->end($this->at(60));
        $session->end($this->at(600));

        self::assertSame(60, $session->getDurationSeconds());
    }

    public function testAnAbandonedSessionEndsAtItsLastActivity(): void
    {
        $session = $this->session(StreetleMode::FREE_PLAY);
        $session->recordCorrectGuess(1, $this->at(90));
        $session->abandon();

        self::assertSame(90, $session->getDurationSeconds());
    }

    public function testStreetsPerMinute(): void
    {
        $session = $this->session(StreetleMode::FREE_PLAY);
        foreach ([1, 2, 3] as $id) {
            $session->recordCorrectGuess($id, $this->at($id * 10));
        }
        $session->end($this->at(120));

        self::assertEqualsWithDelta(1.5, $session->getStreetsPerMinute(), 0.0001);
    }

    private function session(StreetleMode $mode): StreetleSession
    {
        $employee = new Employee();
        $employee->setHotel(new Hotel());

        return new StreetleSession($employee, $mode, $this->start);
    }

    private function at(int $seconds): DateTimeImmutable
    {
        return $this->start->modify("+{$seconds} seconds");
    }
}
