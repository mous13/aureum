<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

interface HotelOwnedInterface
{
    public function getHotel(): ?Hotel;
}
