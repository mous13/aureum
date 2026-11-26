<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

final readonly class GuessOutcome
{
    public function __construct(
        public bool $correct,
        public ?string $name = null,
        public ?string $guessedName = null,
        public ?int $distanceMetres = null,
    ) {
    }
}
