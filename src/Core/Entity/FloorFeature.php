<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\FeatureType;
use Citadel\Aureum\Core\Repository\FloorFeatureRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: FloorFeatureRepository::class)]
#[ORM\Table(name: 'aureum_floor_features')]
class FloorFeature implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Floor::class, inversedBy: 'features')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Floor $floor;

    #[ORM\Column(length: 50, enumType: FeatureType::class)]
    private FeatureType $type;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: 'boolean')]
    private bool $hasSteps = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $contents = null;

    #[ORM\Column(type: 'json')]
    private array $cells = [];

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): void
    {
        $this->department = $department;
    }

    public function getContents(): ?string
    {
        return $this->contents;
    }

    public function setContents(?string $contents): void
    {
        $this->contents = $contents;
    }

    public function getHotel(): ?Hotel
    {
        return $this->floor->getHotel();
    }

    public function getFloor(): Floor
    {
        return $this->floor;
    }

    public function setFloor(Floor $floor): void
    {
        $this->floor = $floor;
    }

    public function getType(): FeatureType
    {
        return $this->type;
    }

    public function setType(FeatureType $type): void
    {
        $this->type = $type;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function hasSteps(): bool
    {
        return $this->type === FeatureType::STAIRS || $this->hasSteps;
    }

    public function getDisplayName(): string
    {
        return $this->label ?? $this->type->value;
    }

    public function setHasSteps(bool $hasSteps): void
    {
        $this->hasSteps = $hasSteps;
    }

    public function getCells(): array
    {
        return $this->cells;
    }

    public function setCells(array $cells): void
    {
        $this->cells = $cells;
    }
}
