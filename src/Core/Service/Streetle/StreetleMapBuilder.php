<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Entity\Enum\StreetKind;

final class StreetleMapBuilder
{
    private const EXCLUDED_HIGHWAYS = ['platform', 'bus_stop', 'construction', 'proposed', 'elevator', 'corridor', 'raceway', 'bus_guideway'];
    private const RAIL_TYPES = ['rail', 'light_rail', 'subway', 'narrow_gauge'];
    private const PARK_TAGS = ['leisure' => ['park', 'garden'], 'landuse' => ['grass', 'village_green']];
    public const FEATURED_PLACES = [
        'Manchester Arndale' => 'shopping',
        'Afflecks' => 'shopping',
        'Manchester Art Gallery' => 'gallery',
    ];

    public function __construct(
        private readonly StreetNameNormalizer $normalizer,
    ) {
    }

    /**
     * @param list<array<mixed>> $elements Overpass elements requested with "out geom"
     */
    public function build(array $elements, StreetleBounds $playArea): StreetleMapData
    {
        $streets = [];
        $basemap = ['buildings' => [], 'green' => [], 'parks' => [], 'water' => [], 'waterways' => [], 'rail' => [], 'tram' => [], 'places' => []];
        $places = [];
        $parkAreas = [];

        foreach ($elements as $element) {
            $tags = $this->tags($element['tags'] ?? null);

            $at = $this->point($element['center'] ?? $element);
            if ($at !== null) {
                $this->addPlace($places, $tags, $at);
                continue;
            }

            $parkName = ($tags['leisure'] ?? null) === 'park' ? trim($tags['name'] ?? '') : '';
            if ($parkName !== '') {
                foreach ($this->rings($element) as $ring) {
                    $parkAreas[] = ['name' => $parkName, 'ring' => $ring];
                }
                continue;
            }

            $line = $this->line($element['geometry'] ?? null);
            if (count($line) < 2) {
                continue;
            }

            if (isset($tags['highway'])) {
                $this->addStreet($streets, $tags, $line);
                continue;
            }

            $layer = $this->basemapLayer($tags);
            if ($layer !== null && (!in_array($layer, ['buildings', 'green', 'water'], true) || $this->isClosed($line))) {
                $basemap[$layer][] = $line;
            }
        }

        $basemap['places'] = array_values(array_filter(
            $places,
            static fn (array $place): bool => $playArea->contains(...$place['at']),
        ));
        $markedParks = [];
        foreach ($basemap['places'] as $place) {
            if ($place['kind'] === 'park') {
                $markedParks[$place['name']] = true;
            }
        }
        foreach ($parkAreas as $area) {
            $basemap[isset($markedParks[$area['name']]) ? 'parks' : 'green'][] = $area['ring'];
        }

        $inPlay = array_filter($streets, static fn (array $street): bool => $playArea->touches($street['lines']));

        return new StreetleMapData(array_values(array_map(
            static fn (array $street): StreetData => new StreetData($street['name'], $street['normalizedName'], $street['kind'], LineJoiner::join($street['lines'])),
            $inPlay,
        )), $basemap);
    }

    /**
     * @param array<string, array{name: string, kind: string, at: array{float, float}}> $places
     * @param array<string, string> $tags
     * @param array{float, float} $at
     */
    private function addPlace(array &$places, array $tags, array $at): void
    {
        $kind = $this->placeKind($tags);
        $name = trim($tags['name'] ?? '');
        if ($kind === null || $name === '') {
            return;
        }

        $places[$kind . $name] ??= ['name' => $name, 'kind' => $kind, 'at' => $at];
    }

    /**
     * @param array<mixed> $element
     * @return list<list<array{float, float}>>
     */
    private function rings(array $element): array
    {
        if (($element['type'] ?? null) !== 'relation') {
            $line = $this->line($element['geometry'] ?? null);

            return $this->isClosed($line) ? [$line] : [];
        }

        $outer = [];
        foreach (is_array($element['members'] ?? null) ? $element['members'] : [] as $member) {
            $line = is_array($member) && ($member['role'] ?? null) === 'outer' ? $this->line($member['geometry'] ?? null) : [];
            if (count($line) >= 2) {
                $outer[] = $line;
            }
        }

        return array_values(array_filter(LineJoiner::join($outer), $this->isClosed(...)));
    }

    /**
     * @param array<string, array{name: string, normalizedName: string, kind: StreetKind, lines: list<list<array{float, float}>>}> $streets
     * @param array<string, string> $tags
     * @param list<array{float, float}> $line
     */
    private function addStreet(array &$streets, array $tags, array $line): void
    {
        $name = trim($tags['name'] ?? '');
        $highway = $tags['highway'] ?? '';
        if ($name === '' || in_array($highway, self::EXCLUDED_HIGHWAYS, true)) {
            return;
        }

        $kind = StreetKind::fromHighway($highway);
        $normalized = $this->normalizer->normalize($name);
        if ($kind === null || $normalized === '') {
            return;
        }

        if (!isset($streets[$normalized])) {
            $streets[$normalized] = ['name' => $name, 'normalizedName' => $normalized, 'kind' => $kind, 'lines' => []];
        }

        $streets[$normalized]['lines'][] = $line;
        if ($kind->prominence() > $streets[$normalized]['kind']->prominence()) {
            $streets[$normalized]['kind'] = $kind;
        }
    }

    /**
     * @param array<string, string> $tags
     */
    private function basemapLayer(array $tags): ?string
    {
        $railway = $tags['railway'] ?? null;

        return match (true) {
            isset($tags['building']) => 'buildings',
            ($tags['natural'] ?? null) === 'water', ($tags['waterway'] ?? null) === 'riverbank' => 'water',
            in_array($tags['waterway'] ?? null, ['river', 'canal', 'stream'], true) => 'waterways',
            $this->matches($tags, self::PARK_TAGS) => 'green',
            in_array($railway, self::RAIL_TYPES, true) => 'rail',
            $railway === 'tram' => 'tram',
            default => null,
        };
    }

    /**
     * @param array<string, string> $tags
     */
    private function placeKind(array $tags): ?string
    {
        $featured = self::FEATURED_PLACES[$tags['name'] ?? ''] ?? null;

        return match (true) {
            ($tags['railway'] ?? null) === 'tram_stop' => 'tram',
            ($tags['railway'] ?? null) === 'station' => 'station',
            $featured !== null => $featured,
            ($tags['tourism'] ?? null) === 'museum' => 'museum',
            ($tags['leisure'] ?? null) === 'park' => 'park',
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private function tags(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        return array_filter($tags, static fn (mixed $value, int|string $key): bool => is_string($key) && is_string($value), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @param array<string, string> $tags
     * @param array<string, list<string>> $wanted
     */
    private function matches(array $tags, array $wanted): bool
    {
        foreach ($wanted as $key => $values) {
            if (in_array($tags[$key] ?? null, $values, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{float, float}>
     */
    private function line(mixed $geometry): array
    {
        if (!is_array($geometry)) {
            return [];
        }

        $line = [];
        foreach ($geometry as $point) {
            $coordinate = $this->point($point);
            if ($coordinate !== null) {
                $line[] = $coordinate;
            }
        }

        return $line;
    }

    /**
     * @return array{float, float}|null
     */
    private function point(mixed $point): ?array
    {
        $lon = is_array($point) ? $point['lon'] ?? null : null;
        $lat = is_array($point) ? $point['lat'] ?? null : null;
        if (!is_int($lon) && !is_float($lon) || !is_int($lat) && !is_float($lat)) {
            return null;
        }

        return [round($lon, 5), round($lat, 5)];
    }

    /**
     * @param list<array{float, float}> $line
     */
    private function isClosed(array $line): bool
    {
        return count($line) >= 4 && $line[0] === $line[count($line) - 1];
    }
}
