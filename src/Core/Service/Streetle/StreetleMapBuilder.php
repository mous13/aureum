<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Entity\Enum\StreetKind;

final class StreetleMapBuilder
{
    private const EXCLUDED_HIGHWAYS = ['platform', 'bus_stop', 'construction', 'proposed', 'elevator', 'corridor', 'raceway', 'bus_guideway'];
    private const RAIL_TYPES = ['rail', 'light_rail', 'subway', 'narrow_gauge'];
    private const PARK_TAGS = ['leisure' => ['park', 'garden'], 'landuse' => ['grass', 'village_green']];
    private const LANDMARK_TAGS = [
        'tourism' => ['museum', 'gallery', 'attraction'],
        'amenity' => ['theatre', 'arts_centre', 'library', 'townhall', 'cinema', 'concert_hall'],
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
        $basemap = ['buildings' => [], 'parks' => [], 'water' => [], 'waterways' => [], 'rail' => [], 'tram' => [], 'landmarks' => []];
        $landmarkNames = [];

        foreach ($elements as $element) {
            $tags = $this->tags($element['tags'] ?? null);

            if (($element['type'] ?? null) === 'node') {
                $landmark = $this->landmark($tags, $this->point($element));
                if ($landmark !== null && !isset($landmarkNames[$landmark['kind'] . $landmark['name']])) {
                    $landmarkNames[$landmark['kind'] . $landmark['name']] = true;
                    $basemap['landmarks'][] = $landmark;
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
            if ($layer === null) {
                continue;
            }

            if (in_array($layer, ['buildings', 'parks', 'water'], true) && !$this->isClosed($line)) {
                continue;
            }

            $basemap[$layer][] = $line;
        }

        $inPlay = array_filter($streets, static fn (array $street): bool => $playArea->touches($street['lines']));

        return new StreetleMapData(array_values(array_map(
            static fn (array $street): StreetData => new StreetData($street['name'], $street['normalizedName'], $street['kind'], LineJoiner::join($street['lines'])),
            $inPlay,
        )), $basemap);
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
            $this->matches($tags, self::PARK_TAGS) => 'parks',
            in_array($railway, self::RAIL_TYPES, true) => 'rail',
            $railway === 'tram' => 'tram',
            default => null,
        };
    }

    /**
     * @param array<string, string> $tags
     * @param array{float, float}|null $at
     * @return array{name: string, kind: string, at: array{float, float}}|null
     */
    private function landmark(array $tags, ?array $at): ?array
    {
        $name = trim($tags['name'] ?? '');
        if ($name === '' || $at === null) {
            return null;
        }

        $kind = match (true) {
            ($tags['railway'] ?? null) === 'station' => 'station',
            ($tags['railway'] ?? null) === 'tram_stop' => 'tram',
            $this->matches($tags, self::LANDMARK_TAGS) => 'landmark',
            default => null,
        };

        return $kind === null ? null : ['name' => $name, 'kind' => $kind, 'at' => $at];
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
