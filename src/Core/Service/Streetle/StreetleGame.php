<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Entity\StreetleSession;
use Citadel\Aureum\Core\Entity\StreetleStreet;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;
use Citadel\Aureum\Core\Repository\StreetleStreetRepository;
use DateTimeImmutable;

class StreetleGame
{
    public const MAX_HINT_LEVEL = 3;

    public function __construct(
        private readonly StreetleSessionRepository $sessionRepository,
        private readonly StreetleStreetRepository $streetRepository,
        private readonly StreetNameNormalizer $normalizer,
    ) {
    }

    public function start(Employee $employee, StreetleMode $mode, DateTimeImmutable $now): StreetleSession
    {
        $open = $this->sessionRepository->findOpenForEmployee($employee);
        foreach ($open as $session) {
            $session->abandon();
        }

        $session = new StreetleSession($employee, $mode, $now);
        $this->sessionRepository->saveAll([...$open, $session]);

        return $session;
    }

    public function closeIfExpired(StreetleSession $session, DateTimeImmutable $now): bool
    {
        if (!$session->isOpen() || !$session->hasExpired($now)) {
            return false;
        }

        $this->end($session, $now);

        return true;
    }

    public function guess(StreetleSession $session, StreetleStreet $target, string $guess, DateTimeImmutable $now): GuessOutcome
    {
        $normalized = $this->normalizer->normalize($guess);
        if ($normalized === $target->getNormalizedName()) {
            $session->recordCorrectGuess($target->getId(), $now);
            $this->sessionRepository->save($session);

            return new GuessOutcome(true, $target->getName());
        }

        $session->recordWrongGuess($now);
        $this->sessionRepository->save($session);

        $other = $this->streetRepository->findOneByNormalizedName($target->getMap(), $normalized);
        if ($other === null) {
            return new GuessOutcome(false);
        }

        return new GuessOutcome(false, guessedName: $other->getName(), distanceMetres: StreetDistance::metres($other->getLines(), $target->getLines()));
    }

    public function reveal(StreetleSession $session, StreetleStreet $target, DateTimeImmutable $now): void
    {
        $session->reveal($target->getId(), $now);
        $this->sessionRepository->save($session);
    }

    public function hint(StreetleStreet $target, int $level): string
    {
        $shown = max(1, min(self::MAX_HINT_LEVEL, $level)) - 1;

        $words = array_map(static function (string $word) use ($shown): string {
            $letters = mb_str_split($word);
            foreach ($letters as $i => $letter) {
                if ($i >= $shown && preg_match('/\p{L}/u', $letter) === 1) {
                    $letters[$i] = '_';
                }
            }

            return implode('', $letters);
        }, explode(' ', $target->getName()));

        return implode('  ', $words);
    }

    public function end(StreetleSession $session, DateTimeImmutable $now): void
    {
        $session->end($now);
        $this->sessionRepository->save($session);
    }
}
