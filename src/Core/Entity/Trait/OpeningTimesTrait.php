<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Trait;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait OpeningTimesTrait
{
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $openingTimes = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googlePlaceId = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $openingTimesSyncedAt = null;

    public function getOpeningTimes(): ?array
    {
        return $this->openingTimes;
    }

    public function setOpeningTimes(?array $openingTimes): void
    {
        $this->openingTimes = $openingTimes;
    }

    public function getGooglePlaceId(): ?string
    {
        return $this->googlePlaceId;
    }

    public function setGooglePlaceId(?string $googlePlaceId): void
    {
        $this->googlePlaceId = $googlePlaceId;
    }

    public function getOpeningTimesSyncedAt(): ?DateTime
    {
        return $this->openingTimesSyncedAt;
    }

    public function setOpeningTimesSyncedAt(?DateTime $openingTimesSyncedAt): void
    {
        $this->openingTimesSyncedAt = $openingTimesSyncedAt;
    }
}
