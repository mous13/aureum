<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\LogAction;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Entity\RestaurantLog;
use Citadel\Aureum\Core\Repository\RestaurantLogRepository;

class RestaurantLogService
{
    public function __construct(
        private readonly RestaurantLogRepository $restaurantLogRepository,
    ) {
    }

    public function logCreated(Restaurant $restaurant, Employee $employee): void
    {
        $log = new RestaurantLog();
        $log->setRestaurant($restaurant);
        $log->setAction(LogAction::CREATED);
        $log->setPerformedBy($employee);
        $log->setHotel($restaurant->getHotel());

        $log->setChanges([
            'name' => $restaurant->getName(),
            'neighbourhood' => $restaurant->getNeighbourhood(),
            'street' => $restaurant->getStreet(),
            'cuisines' => $restaurant->getCuisines()->map(fn($c) => $c->getName())->toArray(),
            'dietaryRequirements' => $restaurant->getDietaryRequirements(),
            'mealPeriods' => $restaurant->getMealPeriods(),
            'tags' => $restaurant->getTags()->map(fn($t) => $t->getName())->toArray(),
            'connections' => $restaurant->getConnections()->map(fn($e) => $e->getName())->toArray(),
        ]);
        $this->restaurantLogRepository->save($log);
    }

    public function logUpdated(Restaurant $restaurant, array $originalData, Employee $employee): void
    {
        $changes = $this->detectChanges($restaurant, $originalData);

        if (empty($changes)) {
            return;
        }

        $log = new RestaurantLog();
        $log->setRestaurant($restaurant);
        $log->setPerformedBy($employee);
        $log->setHotel($restaurant->getHotel());
        $log->setAction(LogAction::UPDATED);
        $log->setChanges($changes);
        $this->restaurantLogRepository->save($log);
    }

    private function detectChanges(Restaurant $new, array $originalData): array
    {
        $changes = [];

        if ($originalData['name'] !== $new->getName()) {
            $changes['name'] = [
                'old' => $originalData['name'],
                    'new' => $new->getName(),
                ];
        }

        if ($originalData['neighbourhood'] !== $new->getNeighbourhood()) {
            $changes['neighbourhood'] = [
                'old' => $originalData['neighbourhood'],
                'new' => $new->getNeighbourhood(),
            ];
        }

        if ($originalData['street'] !== $new->getStreet()) {
            $changes['street'] = [
                'old' => $originalData['street'],
                'new' => $new->getStreet(),
            ];
        }

        if ($originalData['dietaryRequirements'] !== $new->getDietaryRequirements()) {
            $changes['dietaryRequirements'] = [
                'old' => $originalData['dietaryRequirements'],
                'new' => $new->getDietaryRequirements(),
            ];
        }

        if ($originalData['mealPeriods'] !== $new->getMealPeriods()) {
            $changes['mealPeriods'] = [
                'old' => $originalData['mealPeriods'],
                'new' => $new->getMealPeriods(),
            ];
        }

        $newCuisines = $new->getCuisines()->map(fn($c) => $c->getName())->toArray();
        if ($originalData['cuisines'] !== $newCuisines) {
            $changes['cuisines'] = [
                'old' => $originalData['cuisines'],
                'new' => $newCuisines,
            ];
        }

        $newTags = $new->getTags()->map(fn($t) => $t->getName())->toArray();
        if ($originalData['tags'] !== $newTags) {
            $changes['tags'] = [
                'old' => $originalData['tags'],
                'new' => $newTags]
            ;
        }

        $newConnections = $new->getConnections()->map(fn($e) => $e->getName())->toArray();
        if ($originalData['connections'] !== $newConnections) {
            $changes['connections'] = [
                'old' => $originalData['connections'],
                'new' => $newConnections,
            ];
        }

        return $changes;
    }

    public function captureCurrentState(Restaurant $restaurant): array
    {
        return [
            'name' => $restaurant->getName(),
            'neighbourhood' => $restaurant->getNeighbourhood(),
            'street' => $restaurant->getStreet(),
            'cuisines' => $restaurant->getCuisines()->map(fn($c) => $c->getName())->toArray(),
            'dietaryRequirements' => $restaurant->getDietaryRequirements(),
            'mealPeriods' => $restaurant->getMealPeriods(),
            'tags' => $restaurant->getTags()->map(fn($t) => $t->getName())->toArray(),
            'connections' => $restaurant->getConnections()->map(fn($e) => $e->getName())->toArray(),
        ];
    }
}
