<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Repository\RestaurantRepository;
use DateTime;

class RestaurantGoogleService
{
    public function __construct(
        private readonly GooglePlacesClient $client,
        private readonly GoogleOpeningHoursTranslator $translator,
        private readonly RestaurantRepository $restaurantRepository,
    ) {
    }

    public function search(Restaurant $restaurant): array
    {
        $query = implode(', ', array_filter([
            $restaurant->getName(),
            $restaurant->getStreet(),
            $restaurant->getNeighbourhood(),
        ]));

        return $this->client->searchText($query);
    }

    public function link(Restaurant $restaurant, string $placeId): ?array
    {
        $restaurant->setGooglePlaceId($placeId);
        $times = $this->applyGoogleHours($restaurant);
        $this->restaurantRepository->save($restaurant);

        return $times;
    }

    public function unlink(Restaurant $restaurant): void
    {
        $restaurant->setGooglePlaceId(null);
        $this->restaurantRepository->save($restaurant);
    }

    public function sync(Restaurant $restaurant): bool
    {
        $times = $this->applyGoogleHours($restaurant);
        if ($times === null) {
            return false;
        }

        $this->restaurantRepository->save($restaurant);

        return true;
    }

    private function applyGoogleHours(Restaurant $restaurant): ?array
    {
        $placeId = $restaurant->getGooglePlaceId();
        if ($placeId === null) {
            return null;
        }

        $times = $this->translator->translate($this->client->getOpeningHours($placeId));
        if ($times === null) {
            return null;
        }

        $restaurant->setOpeningTimes($times);
        $restaurant->setOpeningTimesSyncedAt(new DateTime());

        return $times;
    }
}
