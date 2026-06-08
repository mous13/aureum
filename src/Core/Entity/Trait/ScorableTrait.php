<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait ScorableTrait
{
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $score = 0;

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    public function upvote(): void
    {
        $this->score += 2;
    }

    public function downvote(): void
    {
        $this->score -= 1;
    }
}
