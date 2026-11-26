<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: StreetleSessionRepository::class)]
#[ORM\Table(name: 'aureum_streetle_sessions')]
#[ORM\Index(name: 'idx_streetle_session_board', columns: ['hotel_id', 'mode', 'ended_at'])]
class StreetleSession implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    private const GRACE_SECONDS = 2;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Hotel $hotel;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Employee $employee;

    #[ORM\Column(length: 20, enumType: StreetleMode::class)]
    private StreetleMode $mode;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $lastActivityAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $guessCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $correctCount = 0;

    /** @var list<int> */
    #[ORM\Column(type: 'json')]
    private array $namedStreetIds = [];

    /** @var list<int> */
    #[ORM\Column(type: 'json')]
    private array $revealedStreetIds = [];

    public function __construct(Employee $employee, StreetleMode $mode, DateTimeImmutable $now)
    {
        $this->employee = $employee;
        $this->hotel = $employee->getHotel();
        $this->mode = $mode;
        $this->startedAt = $now;
        $this->lastActivityAt = $now;
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function getMode(): StreetleMode
    {
        return $this->mode;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function getDeadline(): ?DateTimeImmutable
    {
        $limit = $this->mode->getTimeLimitSeconds();

        return $limit === null ? null : $this->startedAt->modify("+{$limit} seconds");
    }

    public function isOpen(): bool
    {
        return $this->endedAt === null;
    }

    public function hasExpired(DateTimeImmutable $now): bool
    {
        $deadline = $this->getDeadline();

        return $deadline !== null && $now > $deadline->modify('+' . self::GRACE_SECONDS . ' seconds');
    }

    public function isSettled(int $streetId): bool
    {
        return in_array($streetId, $this->namedStreetIds, true) || in_array($streetId, $this->revealedStreetIds, true);
    }

    public function recordCorrectGuess(int $streetId, DateTimeImmutable $now): void
    {
        $this->guessCount++;
        $this->correctCount++;
        $this->namedStreetIds[] = $streetId;
        $this->lastActivityAt = $now;
    }

    public function recordWrongGuess(DateTimeImmutable $now): void
    {
        $this->guessCount++;
        $this->lastActivityAt = $now;
    }

    public function reveal(int $streetId, DateTimeImmutable $now): void
    {
        $this->revealedStreetIds[] = $streetId;
        $this->lastActivityAt = $now;
    }

    public function end(DateTimeImmutable $now): void
    {
        if ($this->endedAt !== null) {
            return;
        }

        $deadline = $this->getDeadline();
        $this->endedAt = $deadline !== null && $now > $deadline ? $deadline : $now;
    }

    public function abandon(): void
    {
        $this->end($this->lastActivityAt);
    }

    public function getGuessCount(): int
    {
        return $this->guessCount;
    }

    public function getCorrectCount(): int
    {
        return $this->correctCount;
    }

    /** @return list<int> */
    public function getNamedStreetIds(): array
    {
        return $this->namedStreetIds;
    }

    /** @return list<int> */
    public function getRevealedStreetIds(): array
    {
        return $this->revealedStreetIds;
    }

    public function getDurationSeconds(): int
    {
        $end = $this->endedAt ?? $this->lastActivityAt;

        return max(0, $end->getTimestamp() - $this->startedAt->getTimestamp());
    }

    public function getAccuracy(): ?float
    {
        return $this->guessCount === 0 ? null : $this->correctCount / $this->guessCount;
    }

    public function getStreetsPerMinute(): float
    {
        $seconds = $this->getDurationSeconds();

        return $seconds === 0 ? 0.0 : $this->correctCount / ($seconds / 60);
    }
}
