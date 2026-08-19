<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Trait;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait AnonymisableEntityTrait
{
    public const ANONYMISED_PLACEHOLDER = '[removed]';

    #[ORM\Column(name: 'anonymised_at', type: 'datetime', nullable: true)]
    private ?DateTime $anonymisedAt = null;

    public function getAnonymisedAt(): ?DateTime
    {
        return $this->anonymisedAt;
    }

    public function isAnonymised(): bool
    {
        return $this->anonymisedAt !== null;
    }

    protected function markAnonymised(): void
    {
        $this->anonymisedAt = new DateTime();
    }
}
