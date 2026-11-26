<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service\Streetle;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\StreetKind;
use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleMap;
use Citadel\Aureum\Core\Entity\StreetleSession;
use Citadel\Aureum\Core\Entity\StreetleStreet;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;
use Citadel\Aureum\Core\Repository\StreetleStreetRepository;
use Citadel\Aureum\Core\Service\Streetle\StreetData;
use Citadel\Aureum\Core\Service\Streetle\StreetleBounds;
use Citadel\Aureum\Core\Service\Streetle\StreetleGame;
use Citadel\Aureum\Core\Service\Streetle\StreetNameNormalizer;
use Citadel\Aureum\Tests\Unit\EntityIdTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StreetleGameTest extends TestCase
{
    use EntityIdTrait;

    private StreetleMap $map;
    private StreetleStreet $deansgate;
    private StreetleStreet $quay;
    private StreetleSessionRepository $sessions;
    private StreetleGame $game;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-11-26 10:00:00');
        $this->map = new StreetleMap(new Hotel(), StreetleBounds::manchesterCityCentre());
        $this->deansgate = $this->street(1, 'Deansgate', [[[-2.2480, 53.4800], [-2.2480, 53.4810]]]);
        $this->quay = $this->street(2, 'Quay Street', [[[-2.2500, 53.4800], [-2.2520, 53.4800]]]);

        $streets = $this->createMock(StreetleStreetRepository::class);
        $streets->method('findOneByNormalizedName')->willReturnCallback(
            fn (StreetleMap $map, string $name): ?StreetleStreet => match ($name) {
                'deansgate' => $this->deansgate,
                'quay st' => $this->quay,
                default => null,
            },
        );

        $this->sessions = $this->createMock(StreetleSessionRepository::class);
        $this->game = new StreetleGame($this->sessions, $streets, new StreetNameNormalizer());
    }

    public function testStartingClosesAnyOpenSession(): void
    {
        $employee = $this->employee();
        $open = new StreetleSession($employee, StreetleMode::FREE_PLAY, $this->now->modify('-1 hour'));
        $this->sessions->method('findOpenForEmployee')->willReturn([$open]);
        $this->sessions->expects(self::once())->method('saveAll');

        $session = $this->game->start($employee, StreetleMode::SPRINT, $this->now);

        self::assertFalse($open->isOpen());
        self::assertTrue($session->isOpen());
        self::assertSame(StreetleMode::SPRINT, $session->getMode());
    }

    public function testACorrectGuessNamesTheStreet(): void
    {
        $session = $this->session();

        $outcome = $this->game->guess($session, $this->deansgate, ' deansgate ', $this->now);

        self::assertTrue($outcome->correct);
        self::assertSame('Deansgate', $outcome->name);
        self::assertSame([1], $session->getNamedStreetIds());
    }

    public function testAWrongGuessOfARealStreetSaysHowFarAwayItIs(): void
    {
        $session = $this->session();

        $outcome = $this->game->guess($session, $this->deansgate, 'Quay St', $this->now);

        self::assertFalse($outcome->correct);
        self::assertNull($outcome->name);
        self::assertSame('Quay Street', $outcome->guessedName);
        self::assertEqualsWithDelta(133, $outcome->distanceMetres, 5);
        self::assertSame(1, $session->getGuessCount());
        self::assertSame(0, $session->getCorrectCount());
    }

    public function testAWrongGuessOfAnUnknownStreetHasNoDistance(): void
    {
        $outcome = $this->game->guess($this->session(), $this->deansgate, 'Nowhere Lane', $this->now);

        self::assertFalse($outcome->correct);
        self::assertNull($outcome->guessedName);
        self::assertNull($outcome->distanceMetres);
    }

    public function testRevealingSkipsTheStreetWithoutCountingAGuess(): void
    {
        $session = $this->session();

        $this->game->reveal($session, $this->quay, $this->now);

        self::assertSame([2], $session->getRevealedStreetIds());
        self::assertSame(0, $session->getGuessCount());
    }

    public function testHintsUncoverOneMoreLetterPerWordUpToThree(): void
    {
        self::assertSame('____  ______', $this->game->hint($this->quay, 1));
        self::assertSame('Q___  S_____', $this->game->hint($this->quay, 2));
        self::assertSame('Qu__  St____', $this->game->hint($this->quay, 3));
        self::assertSame('Qu__  St____', $this->game->hint($this->quay, 9));
        self::assertSame('____  ______', $this->game->hint($this->quay, 0));
    }

    public function testAnExpiredSprintIsClosed(): void
    {
        $session = new StreetleSession($this->employee(), StreetleMode::SPRINT, $this->now);
        $this->sessions->expects(self::once())->method('save');

        self::assertFalse($this->game->closeIfExpired($session, $this->now->modify('+100 seconds')));
        self::assertTrue($this->game->closeIfExpired($session, $this->now->modify('+400 seconds')));
        self::assertFalse($session->isOpen());
        self::assertSame(300, $session->getDurationSeconds());
    }

    private function session(): StreetleSession
    {
        return new StreetleSession($this->employee(), StreetleMode::FREE_PLAY, $this->now);
    }

    private function employee(): Employee
    {
        $employee = new Employee();
        $employee->setHotel(new Hotel());

        return $employee;
    }

    /**
     * @param list<list<array{float, float}>> $lines
     */
    private function street(int $id, string $name, array $lines): StreetleStreet
    {
        $normalizer = new StreetNameNormalizer();
        $street = new StreetleStreet($this->map, new StreetData($name, $normalizer->normalize($name), StreetKind::MINOR, $lines));

        return $this->withId($street, $id);
    }
}
