<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\BathroomStyle;
use Citadel\Aureum\Core\Entity\Enum\BedSize;
use Citadel\Aureum\Core\Entity\Enum\RoomView;
use Citadel\Aureum\Core\Repository\RoomRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[ORM\Table(name: 'aureum_rooms')]
#[ORM\UniqueConstraint(name: 'uniq_floor_number', columns: ['floor_id', 'number'])]
class Room
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Floor::class, inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Floor $floor;

    #[ORM\Column(length: 20)]
    private string $number;

    #[ORM\ManyToOne(targetEntity: RoomType::class)]
    #[ORM\JoinColumn(name: 'room_type_id', nullable: false, onDelete: 'RESTRICT')]
    private RoomType $roomType;

    #[ORM\Column(length: 50, enumType: BathroomStyle::class)]
    private BathroomStyle $bathroomStyle;

    #[ORM\Column(name: 'room_view', length: 50, enumType: RoomView::Class)]
    private RoomView $view;

    #[ORM\Column(length: 50, enumType: BedSize::class)]
    private BedSize $bedSize;

    #[ORM\Column(type: 'boolean')]
    private bool $hasStairs = false;

    #[ORM\Column(type: 'boolean')]
    private bool $interconnecting = false;

    #[ORM\Column(type: 'boolean')]
    private bool $lastLet = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: 'json')]
    private array $cells = [];

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): void
    {
        $this->comments = $comments;
    }

    public function getFloor(): Floor
    {
        return $this->floor;
    }

    public function setFloor(Floor $floor): void
    {
        $this->floor = $floor;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getRoomType(): RoomType
    {
        return $this->roomType;
    }

    public function setRoomType(RoomType $roomType): void
    {
        $this->roomType = $roomType;
    }

    public function getBathroomStyle(): BathroomStyle
    {
        return $this->bathroomStyle;
    }

    public function setBathroomStyle(BathroomStyle $bathroomStyle): void
    {
        $this->bathroomStyle = $bathroomStyle;
    }

    public function getView(): RoomView
    {
        return $this->view;
    }

    public function setView(RoomView $view): void
    {
        $this->view = $view;
    }

    public function getBedSize(): BedSize
    {
        return $this->bedSize;
    }

    public function setBedSize(BedSize $bedSize): void
    {
        $this->bedSize = $bedSize;
    }

    public function hasStairs(): bool
    {
        return $this->hasStairs;
    }

    public function setHasStairs(bool $hasStairs): void
    {
        $this->hasStairs = $hasStairs;
    }

    public function isInterconnecting(): bool
    {
        return $this->interconnecting;
    }

    public function setInterconnecting(bool $interconnecting): void
    {
        $this->interconnecting = $interconnecting;
    }

    public function isLastLet(): bool
    {
        return $this->lastLet;
    }

    public function setLastLet(bool $lastLet): void
    {
        $this->lastLet = $lastLet;
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
