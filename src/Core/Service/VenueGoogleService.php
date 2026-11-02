<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\VenueInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Repository\AbstractRepository;

class VenueGoogleService
{
    public function __construct(
        private readonly GooglePlacesClient $client,
        private readonly GoogleOpeningHoursTranslator $translator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function search(VenueInterface $venue): array
    {
        $query = implode(', ', array_filter([
            $venue->getName(),
            $venue->getStreet(),
            $venue->getNeighbourhood(),
        ]));

        return $this->client->searchText($query);
    }

    public function link(VenueInterface $venue, string $placeId): ?array
    {
        $venue->setGooglePlaceId($placeId);
        $times = $this->applyGoogleHours($venue);
        $this->save($venue);

        return $times;
    }

    public function unlink(VenueInterface $venue): void
    {
        $venue->setGooglePlaceId(null);
        $this->save($venue);
    }

    public function sync(VenueInterface $venue): bool
    {
        $times = $this->applyGoogleHours($venue);
        if ($times === null) {
            return false;
        }

        $this->save($venue);

        return true;
    }

    private function applyGoogleHours(VenueInterface $venue): ?array
    {
        $placeId = $venue->getGooglePlaceId();
        if ($placeId === null) {
            return null;
        }

        $times = $this->translator->translate($this->client->getOpeningHours($placeId));
        if ($times === null) {
            return null;
        }

        $venue->setOpeningTimes($times);
        $venue->setOpeningTimesSyncedAt(new DateTime());

        return $times;
    }

    private function save(VenueInterface $venue): void
    {
        $repository = $this->entityManager->getRepository($venue::class);
        if ($repository instanceof AbstractRepository) {
            $repository->save($venue);
            return;
        }

        $this->entityManager->persist($venue);
        $this->entityManager->flush();
    }
}
