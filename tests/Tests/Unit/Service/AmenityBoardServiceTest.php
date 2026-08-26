<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Entity\AmenityBoard;
use Citadel\Aureum\Core\Entity\AmenityCard;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\AmenityCardStatus;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Repository\AmenityBoardRepository;
use Citadel\Aureum\Core\Repository\AmenityCardRepository;
use Citadel\Aureum\Core\Service\AmenityBoardService;
use Citadel\Aureum\Tests\Unit\EntityIdTrait;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;

class AmenityBoardServiceTest extends TestCase
{
    use EntityIdTrait;

    public function testTurnoverIsDueWhenThereIsNoBoard(): void
    {
        $service = $this->service();

        self::assertTrue($service->isTurnoverDue(null, new DateTimeImmutable('2026-08-26')));
    }

    public function testTurnoverIsDueWhenTheLatestBoardIsFromYesterday(): void
    {
        $board = new AmenityBoard();
        $board->setDate(new DateTime('2026-08-25'));

        self::assertTrue($this->service()->isTurnoverDue($board, new DateTimeImmutable('2026-08-26')));
    }

    public function testTurnoverIsNotDueWhenTheBoardIsFromToday(): void
    {
        $board = new AmenityBoard();
        $board->setDate(new DateTime('2026-08-26'));

        self::assertFalse($this->service()->isTurnoverDue($board, new DateTimeImmutable('2026-08-26')));
        self::assertFalse($this->service()->isTurnoverDue($board, new DateTimeImmutable('2026-08-25')));
    }

    public function testStartBoardCreatesAnEmptyBoardForToday(): void
    {
        $hotel = new Hotel();
        $employee = new Employee();
        $boards = $this->createMock(AmenityBoardRepository::class);
        $boards->expects(self::once())->method('save');

        $board = $this->service($boards)->startBoard($hotel, $employee, new DateTimeImmutable('2026-08-26 14:30:00'));

        self::assertSame('2026-08-26 00:00:00', $board->getDate()->format('Y-m-d H:i:s'));
        self::assertSame($hotel, $board->getHotel());
        self::assertSame($employee, $board->getCreatedBy());
        self::assertCount(0, $board->getCards());
    }

    public function testStartBoardReturnsTheExistingBoardWhenAnotherManagerWonTheRace(): void
    {
        $hotel = new Hotel();
        $existing = new AmenityBoard();
        $boards = $this->createMock(AmenityBoardRepository::class);
        $boards->method('save')->willThrowException(
            $this->createStub(UniqueConstraintViolationException::class),
        );
        $boards->method('findOneBy')->willReturn($existing);

        $board = $this->service($boards)->startBoard($hotel, new Employee(), new DateTimeImmutable('2026-08-26'));

        self::assertSame($existing, $board);
    }

    public function testMovingWithinAColumnRenumbersGapFree(): void
    {
        $board = $this->board();
        $first = $this->card($board, 1, AmenityCardStatus::READY, 0);
        $second = $this->card($board, 2, AmenityCardStatus::READY, 1);
        $third = $this->card($board, 3, AmenityCardStatus::READY, 2);

        $this->service()->moveCard($first, AmenityCardStatus::READY, 2);

        self::assertSame(2, $first->getPosition());
        self::assertSame(0, $second->getPosition());
        self::assertSame(1, $third->getPosition());
    }

    public function testMovingAcrossColumnsRenumbersBothColumns(): void
    {
        $board = $this->board();
        $moving = $this->card($board, 1, AmenityCardStatus::NOT_STARTED, 0);
        $stays = $this->card($board, 2, AmenityCardStatus::NOT_STARTED, 1);
        $target = $this->card($board, 3, AmenityCardStatus::IN_PROGRESS, 0);

        $this->service()->moveCard($moving, AmenityCardStatus::IN_PROGRESS, 0);

        self::assertSame(AmenityCardStatus::IN_PROGRESS, $moving->getStatus());
        self::assertSame(0, $moving->getPosition());
        self::assertSame(1, $target->getPosition());
        self::assertSame(0, $stays->getPosition());
    }

    public function testPositionsAreClampedIntoTheColumn(): void
    {
        $board = $this->board();
        $card = $this->card($board, 1, AmenityCardStatus::READY, 0);
        $other = $this->card($board, 2, AmenityCardStatus::COMPLETED, 0);

        $this->service()->moveCard($card, AmenityCardStatus::COMPLETED, -5);
        self::assertSame(0, $card->getPosition());
        self::assertSame(1, $other->getPosition());

        $this->service()->moveCard($card, AmenityCardStatus::COMPLETED, 99);
        self::assertSame(1, $card->getPosition());
        self::assertSame(0, $other->getPosition());
    }

    public function testMovingTouchesTheCard(): void
    {
        $board = $this->board();
        $card = $this->card($board, 1, AmenityCardStatus::READY, 0);
        $card->setUpdatedAt(new DateTime('2020-01-01 00:00:00'));

        $this->service()->moveCard($card, AmenityCardStatus::IN_PROGRESS, 0);

        self::assertGreaterThan(new DateTime('2020-01-01 00:00:00'), $card->getUpdatedAt());
    }

    public function testAdvancingAppendsToTheEndOfTheNextColumn(): void
    {
        $board = $this->board();
        $card = $this->card($board, 1, AmenityCardStatus::READY, 0);
        $existing = $this->card($board, 2, AmenityCardStatus::IN_PROGRESS, 0);

        $this->service()->advanceCard($card);

        self::assertSame(AmenityCardStatus::IN_PROGRESS, $card->getStatus());
        self::assertSame(1, $card->getPosition());
        self::assertSame(0, $existing->getPosition());
    }

    public function testACompletedCardCannotAdvance(): void
    {
        $board = $this->board();
        $card = $this->card($board, 1, AmenityCardStatus::COMPLETED, 0);

        $this->service()->advanceCard($card);

        self::assertSame(AmenityCardStatus::COMPLETED, $card->getStatus());
        self::assertSame(0, $card->getPosition());
    }

    private function service(?AmenityBoardRepository $boards = null): AmenityBoardService
    {
        return new AmenityBoardService(
            $boards ?? $this->createStub(AmenityBoardRepository::class),
            $this->createStub(AmenityCardRepository::class),
        );
    }

    private function board(): AmenityBoard
    {
        $board = new AmenityBoard();
        $this->withId($board, 1);

        return $board;
    }

    private function card(AmenityBoard $board, int $id, AmenityCardStatus $status, int $position): AmenityCard
    {
        $card = new AmenityCard();
        $card->setStatus($status);
        $card->setPosition($position);
        $board->addCard($card);
        $this->withId($card, $id);

        return $card;
    }
}
