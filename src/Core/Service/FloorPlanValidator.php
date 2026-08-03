<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Floor;
use Citadel\Aureum\Core\Entity\FloorFeature;
use Citadel\Aureum\Core\Entity\Room;
use Citadel\Aureum\Core\Repository\FloorFeatureRepository;
use Citadel\Aureum\Core\Repository\RoomRepository;

class FloorPlanValidator
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly FloorFeatureRepository $floorFeatureRepository,
    ) {
    }

    public function normalizeCells(mixed $cells, Floor $floor): ?array
    {
        if (!is_array($cells) || $cells === []) {
            return null;
        }

        $normalized = [];
        foreach ($cells as $cell) {
            if (!is_array($cell) || count($cell) !== 2 || !is_int($cell[0]) || !is_int($cell[1])) {
                return null;
            }

            [$x, $y] = $cell;
            if ($x < 0 || $y < 0 || $x >= $floor->getGridWidth() || $y >= $floor->getGridHeight()) {
                return null;
            }

            $normalized["{$x}:{$y}"] = [$x, $y];
        }

        return array_values($normalized);
    }

    public function cellsOverlap(
        Floor $floor,
        array $cells,
        ?Room $ignoreRoom = null,
        ?FloorFeature $ignoreFeature = null,
    ): bool {
        $wanted = [];
        foreach ($cells as [$x, $y]) {
            $wanted["{$x}:{$y}"] = true;
        }

        foreach ($this->roomRepository->findByFloor($floor) as $room) {
            if ($ignoreRoom !== null && $room->getId() === $ignoreRoom->getId()) {
                continue;
            }

            foreach ($room->getCells() as [$x, $y]) {
                if (isset($wanted["{$x}:{$y}"])) {
                    return true;
                }
            }
        }

        foreach ($this->floorFeatureRepository->findByFloor($floor) as $feature) {
            if ($ignoreFeature !== null && $feature->getId() === $ignoreFeature->getId()) {
                continue;
            }

            foreach ($feature->getCells() as [$x, $y]) {
                if (isset($wanted["{$x}:{$y}"])) {
                    return true;
                }
            }
        }

        return false;
    }
}
