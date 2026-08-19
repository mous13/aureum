<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\Module;
use DateTimeInterface;

interface AnonymisableInterface extends HotelOwnedInterface
{
    public function getId(): int;

    public static function getModule(): Module;

    public function getRetentionAnchor(): ?DateTimeInterface;

    public function anonymise(): void;

    public function isAnonymised(): bool;
}
