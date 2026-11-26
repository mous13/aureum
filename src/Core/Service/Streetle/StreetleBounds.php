<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

final readonly class StreetleBounds
{
    public function __construct(
        public float $west,
        public float $south,
        public float $east,
        public float $north,
    ) {
    }

    public static function manchesterCityCentre(): self
    {
        return new self(-2.2600, 53.4700, -2.2250, 53.4900);
    }

    public function padded(float $degrees): self
    {
        return new self($this->west - $degrees, $this->south - $degrees, $this->east + $degrees, $this->north + $degrees);
    }

    public function contains(float $lng, float $lat): bool
    {
        return $lng >= $this->west && $lng <= $this->east && $lat >= $this->south && $lat <= $this->north;
    }

    /**
     * @param list<list<array{float, float}>> $lines
     */
    public function touches(array $lines): bool
    {
        foreach ($lines as $line) {
            foreach ($line as [$lng, $lat]) {
                if ($this->contains($lng, $lat)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array{float, float} */
    public function getCentre(): array
    {
        return [($this->west + $this->east) / 2, ($this->south + $this->north) / 2];
    }

    /** @return array{float, float, float, float} */
    public function toArray(): array
    {
        return [$this->west, $this->south, $this->east, $this->north];
    }
}
