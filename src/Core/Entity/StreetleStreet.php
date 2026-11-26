<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\StreetKind;
use Citadel\Aureum\Core\Repository\StreetleStreetRepository;
use Citadel\Aureum\Core\Service\Streetle\StreetData;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: StreetleStreetRepository::class)]
#[ORM\Table(name: 'aureum_streetle_streets')]
#[ORM\Index(name: 'idx_streetle_street_name', columns: ['map_id', 'normalized_name'])]
class StreetleStreet
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StreetleMap $map;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $normalizedName;

    #[ORM\Column(length: 10, enumType: StreetKind::class)]
    private StreetKind $kind;

    /** @var list<list<array{float, float}>> */
    #[ORM\Column(name: 'shape', type: 'json')]
    private array $lines;

    public function __construct(StreetleMap $map, StreetData $data)
    {
        $this->map = $map;
        $this->name = $data->name;
        $this->normalizedName = $data->normalizedName;
        $this->kind = $data->kind;
        $this->lines = $data->lines;
    }

    public function getMap(): StreetleMap
    {
        return $this->map;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }

    public function getKind(): StreetKind
    {
        return $this->kind;
    }

    /** @return list<list<array{float, float}>> */
    public function getLines(): array
    {
        return $this->lines;
    }
}
