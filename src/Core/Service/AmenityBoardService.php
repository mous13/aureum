<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\AmenityBoard;
use Citadel\Aureum\Core\Entity\AmenityCard;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\AmenityCardStatus;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Repository\AmenityBoardRepository;
use Citadel\Aureum\Core\Repository\AmenityCardRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class AmenityBoardService
{
    public function __construct(
        private readonly AmenityBoardRepository $boardRepository,
        private readonly AmenityCardRepository $cardRepository,
    ) {
    }

    public function isTurnoverDue(?AmenityBoard $latest, DateTimeImmutable $today): bool
    {
        return $latest === null || $latest->getDate()->format('Y-m-d') < $today->format('Y-m-d');
    }

    public function startBoard(Hotel $hotel, Employee $employee, DateTimeImmutable $today): AmenityBoard
    {
        $board = new AmenityBoard();
        $board->setHotel($hotel);
        $board->setCreatedBy($employee);
        $board->setDate(new DateTime($today->format('Y-m-d')));

        try {
            $this->boardRepository->save($board);
        } catch (UniqueConstraintViolationException) {
            $existing = $this->boardRepository->findOneBy(['hotel' => $hotel, 'date' => $board->getDate()]);
            if ($existing !== null) {
                return $existing;
            }

            throw new \RuntimeException('Unable to start a new amenity board.');
        }

        return $board;
    }

    public function moveCard(AmenityCard $card, AmenityCardStatus $target, int $position): void
    {
        $sameColumn = $card->getStatus() === $target;
        $source = $this->column($card->getBoard(), $card->getStatus(), $card);
        $targetColumn = $sameColumn ? $source : $this->column($card->getBoard(), $target, $card);

        $position = max(0, min($position, count($targetColumn)));
        array_splice($targetColumn, $position, 0, [$card]);

        $card->setStatus($target);
        $card->setUpdatedAt(new DateTime());

        if (!$sameColumn) {
            $this->renumber($source);
        }
        $this->renumber($targetColumn);

        $this->cardRepository->save($card);
    }

    public function advanceCard(AmenityCard $card): void
    {
        $next = $card->getStatus()->next();
        if ($next === null) {
            return;
        }

        $this->moveCard($card, $next, PHP_INT_MAX);
    }

    /** @return array<AmenityCard> */
    private function column(AmenityBoard $board, AmenityCardStatus $status, AmenityCard $except): array
    {
        $cards = $board->getCards()
            ->filter(static fn (AmenityCard $c) => $c->getStatus() === $status && $c !== $except)
            ->getValues();

        usort($cards, static fn (AmenityCard $a, AmenityCard $b) => $a->getPosition() <=> $b->getPosition());

        return $cards;
    }

    /** @param array<AmenityCard> $cards */
    private function renumber(array $cards): void
    {
        foreach ($cards as $index => $card) {
            $card->setPosition($index);
        }
    }
}
