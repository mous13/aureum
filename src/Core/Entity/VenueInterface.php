<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use DateTime;

interface VenueInterface extends HotelOwnedInterface
{
    public function getId(): int;

    public function getName(): string;

    public function getNeighbourhood(): string;

    public function getStreet(): string;

    public function getHotel(): Hotel;

    public function getOpeningTimes(): ?array;

    public function setOpeningTimes(?array $openingTimes): void;

    public function getGooglePlaceId(): ?string;

    public function setGooglePlaceId(?string $googlePlaceId): void;

    public function getOpeningTimesSyncedAt(): ?DateTime;

    public function setOpeningTimesSyncedAt(?DateTime $openingTimesSyncedAt): void;
}
