<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleMap;
use Citadel\Aureum\Core\Entity\StreetleStreet;
use Citadel\Aureum\Core\Exception\StreetleException;
use Citadel\Aureum\Core\Repository\StreetleMapRepository;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;
use Citadel\Aureum\Core\Repository\StreetleStreetRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class StreetleMapImporter
{
    private const BASEMAP_PADDING_DEGREES = 0.004;

    public function __construct(
        private readonly OverpassClient $overpassClient,
        private readonly StreetleMapBuilder $builder,
        private readonly StreetleMapRepository $mapRepository,
        private readonly StreetleStreetRepository $streetRepository,
        private readonly StreetleSessionRepository $sessionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function import(Hotel $hotel, StreetleBounds $bounds): StreetleMap
    {
        $elements = $this->overpassClient->fetchArea($bounds->padded(self::BASEMAP_PADDING_DEGREES));
        $data = $this->builder->build($elements, $bounds);
        if ($data->streets === []) {
            throw new StreetleException('No named streets were found in this area.');
        }

        return $this->entityManager->wrapInTransaction(function () use ($hotel, $bounds, $data): StreetleMap {
            $map = $this->mapRepository->findOneByHotel($hotel);
            if ($map === null) {
                $map = new StreetleMap($hotel, $bounds);
            } else {
                $map->setBounds($bounds);
                $this->streetRepository->deleteForMap($map);
                foreach ($this->sessionRepository->findOpenForHotel($hotel) as $session) {
                    $session->abandon();
                }
            }

            $map->replaceContents($data->basemap, count($data->streets), new DateTimeImmutable());
            $this->mapRepository->save($map, false);
            $this->streetRepository->saveAll(array_map(
                static fn (StreetData $street): StreetleStreet => new StreetleStreet($map, $street),
                $data->streets,
            ));

            return $map;
        });
    }
}
