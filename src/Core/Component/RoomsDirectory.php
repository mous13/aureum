<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Enum\BathroomStyle;
use Citadel\Aureum\Core\Entity\Enum\BedSize;
use Citadel\Aureum\Core\Entity\Enum\FeatureType;
use Citadel\Aureum\Core\Entity\Enum\RoomView;
use Citadel\Aureum\Core\Entity\Floor;
use Citadel\Aureum\Core\Entity\Room;
use Citadel\Aureum\Core\Entity\RoomType as RoomTypeEntity;
use Citadel\Aureum\Core\Repository\FloorFeatureRepository;
use Citadel\Aureum\Core\Repository\FloorRepository;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Citadel\Aureum\Core\Repository\RoomRepository;
use Citadel\Aureum\Core\Repository\RoomTypeRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('RoomsDirectory', '@CitadelAureum/core/components/rooms_directory.html.twig')]
#[IsGranted('aureum.module.rooms.view')]
class RoomsDirectory
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $hotelId;

    #[LiveProp(writable: true)]
    public ?int $floorId = null;

    #[LiveProp(writable: true, url: true)]
    public ?int $roomTypeId = null;

    #[LiveProp(writable: true, url: true)]
    public ?string $bathroomStyle = null;

    #[LiveProp(writable: true, url: true)]
    public ?string $view = null;

    #[LiveProp(writable: true, url: true)]
    public ?string $bedSize = null;

    #[LiveProp(writable: true, url: true)]
    public string $stairs = '';

    #[LiveProp(writable: true, url: true)]
    public string $interconnecting = '';

    #[LiveProp(writable: true, url: true)]
    public string $lastLet = '';

    /** @var array<string, bool>|null */
    private ?array $occupancy = null;

    public function __construct(
        private readonly HotelRepository $hotelRepository,
        private readonly FloorRepository $floorRepository,
        private readonly RoomRepository $roomRepository,
        private readonly FloorFeatureRepository $floorFeatureRepository,
        private readonly RoomTypeRepository $roomTypeRepository,
    ) {
    }

    #[LiveAction]
    public function selectFloor(#[LiveArg] int $floorId): void
    {
        $this->floorId = $floorId;
    }

    #[LiveAction]
    public function clearFilters(): void
    {
        $this->roomTypeId = null;
        $this->bathroomStyle = null;
        $this->view = null;
        $this->bedSize = null;
        $this->stairs = '';
        $this->interconnecting = '';
        $this->lastLet = '';
    }

    /**
     * @return array<Floor>
     */
    public function getFloors(): array
    {
        $hotel = $this->hotelRepository->find($this->hotelId);

        return $hotel === null ? [] : $this->floorRepository->findByHotelOrdered($hotel);
    }

    public function getFloor(): ?Floor
    {
        $floors = $this->getFloors();
        if ($floors === []) {
            return null;
        }

        foreach ($floors as $floor) {
            if ($floor->getId() === $this->floorId) {
                return $floor;
            }
        }

        return $floors[0];
    }

    public function hasActiveFilters(): bool
    {
        return $this->roomTypeId !== null
            || $this->bathroomStyle !== null && $this->bathroomStyle !== ''
            || $this->view !== null && $this->view !== ''
            || $this->bedSize !== null && $this->bedSize !== ''
            || $this->stairs !== ''
            || $this->interconnecting !== ''
            || $this->lastLet !== '';
    }

    /**
     * @return array<int, array{
     *     room: Room,
     *     matches: bool,
     *     cells: array<int, array{x: int, y: int, edges: string, label: bool, span: int}>
     * }>
     */
    public function getRoomTiles(): array
    {
        $floor = $this->getFloor();
        if ($floor === null) {
            return [];
        }

        $occupied = $this->getFloorOccupancy($floor);

        $tiles = [];
        foreach ($this->roomRepository->findByFloor($floor) as $room) {
            $tiles[] = [
                'room' => $room,
                'matches' => $this->matchesFilters($room),
                'cells' => $this->computeCells($room->getCells(), $occupied, $floor->getGridWidth()),
            ];
        }

        return $tiles;
    }

    /***
     * @return array<int, array{
     *     feature: FloorFeature,
     *     cells: array<int, array{x: int, y: int, edges: string, label: bool, span: int}>
     * }>
     */
    public function getFeatureTiles(): array
    {
        $floor = $this->getFloor();
        if ($floor === null) {
            return [];
        }

        $occupied = $this->getFloorOccupancy($floor);

        $tiles = [];
        foreach ($this->floorFeatureRepository->findByFloor($floor) as $feature) {
            $tiles[] = [
                'feature' => $feature,
                'cells' => $this->computeCells(
                    $feature->getCells(),
                    $occupied,
                    $floor->getGridWidth(),
                    $feature->getType() === FeatureType::HALLWAY,
                ),
            ];
        }

        return $tiles;
    }

    /**
     * @param array<int, array{0: int, 1: int}> $rawCells
     * @param array<string, bool> $floorOccupied
     * @return array<int, array{x: int, y: int, edges: string, label: bool, span: int}>
     */
    private function computeCells(
        array $rawCells,
        array $floorOccupied,
        int $gridWidth,
        bool $wallEdges = false,
    ): array {
        $occupied = [];
        foreach ($rawCells as [$x, $y]) {
            $occupied["{$x}:{$y}"] = true;
        }

        $labelCell = null;
        foreach ($rawCells as [$x, $y]) {
            if ($labelCell === null || $y < $labelCell[1] || ($y === $labelCell[1] && $x < $labelCell[0])) {
                $labelCell = [$x, $y];
            }
        }

        $labelSpan = 1;
        if ($labelCell !== null) {
            [$labelX, $labelY] = $labelCell;
            while ($labelX + $labelSpan < $gridWidth) {
                $key = ($labelX + $labelSpan) . ':' . $labelY;
                if (!isset($occupied[$key]) && isset($floorOccupied[$key])) {
                    break;
                }

                $labelSpan++;
            }
        }

        $cells = [];
        foreach ($rawCells as [$x, $y]) {
            $neighbours = [
                't' => $x . ':' . ($y - 1),
                'b' => $x . ':' . ($y + 1),
                'l' => ($x - 1) . ':' . $y,
                'r' => ($x + 1) . ':' . $y,
            ];

            $edges = '';
            foreach ($neighbours as $side => $key) {
                if (isset($occupied[$key])) {
                    continue;
                }

                $edges .= " edge-{$side}";
                if ($wallEdges && !isset($floorOccupied[$key])) {
                    $edges .= " wall-{$side}";
                }
            }

            $isLabel = $labelCell !== null && $x === $labelCell[0] && $y === $labelCell[1];

            $cells[] = [
                'x' => $x,
                'y' => $y,
                'edges' => trim($edges),
                'label' => $isLabel,
                'span' => $isLabel ? $labelSpan : 1,
            ];
        }

        return $cells;
    }

    /**
     * @return array<string, bool>
     */
    private function getFloorOccupancy(Floor $floor): array
    {
        if ($this->occupancy !== null) {
            return $this->occupancy;
        }

        $occupied = [];
        foreach ($this->roomRepository->findByFloor($floor) as $room) {
            foreach ($room->getCells() as [$x, $y]) {
                $occupied["{$x}:{$y}"] = true;
            }
        }

        foreach ($this->floorFeatureRepository->findByFloor($floor) as $feature) {
            foreach ($feature->getCells() as [$x, $y]) {
                $occupied["{$x}:{$y}"] = true;
            }
        }

        return $this->occupancy = $occupied;
    }

    public function getMatchCount(): int
    {
        return count(array_filter($this->getRoomTiles(), static fn (array $tile) => $tile['matches']));
    }

    public function getTotalCount(): int
    {
        return count($this->getRoomTiles());
    }

    /**
     *
     * @return array<RoomTypeEntity>
     */
    public function getRoomTypes(): array
    {
        $hotel = $this->hotelRepository->find($this->hotelId);

        return $hotel === null ? [] : $this->roomTypeRepository->findAllByHotel($hotel);
    }

    /**
     * @return array<string, array<string>>
     */
    public function getFilterOptions(): array
    {
        $values = static fn (array $cases) => array_map(static fn ($case) => $case->value, $cases);

        return [
            'bathroomStyle' => $values(BathroomStyle::cases()),
            'view' => $values(RoomView::cases()),
            'bedSize' => $values(BedSize::cases()),
        ];
    }

    private function matchesFilters(Room $room): bool
    {
        if ($this->roomTypeId !== null && $room->getRoomType()->getId() !== $this->roomTypeId) {
            return false;
        }

        if ($this->bathroomStyle !== null
            && $this->bathroomStyle !== ''
            && $room->getBathroomStyle()->value !== $this->bathroomStyle
        ) {
            return false;
        }

        if ($this->view !== null && $this->view !== '' && $room->getView()->value !== $this->view) {
            return false;
        }

        if ($this->bedSize !== null && $this->bedSize !== '' && $room->getBedSize()->value !== $this->bedSize) {
            return false;
        }

        if ($this->stairs !== '' && $room->hasStairs() !== ($this->stairs === '1')) {
            return false;
        }

        if ($this->interconnecting !== '' && $room->isInterconnecting() !== ($this->interconnecting === '1')) {
            return false;
        }

        if ($this->lastLet !== '' && $room->isLastLet() !== ($this->lastLet === '1')) {
            return false;
        }

        return true;
    }
}
