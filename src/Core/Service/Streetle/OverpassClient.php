<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

use Citadel\Aureum\Core\Exception\StreetleException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OverpassClient
{
    public const ENDPOINTS = [
        'https://overpass-api.de/api/interpreter',
        'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
        'https://overpass.private.coffee/api/interpreter',
    ];

    private const TIMEOUT_SECONDS = 90;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return list<array<mixed>>
     */
    public function fetchArea(StreetleBounds $bounds): array
    {
        $query = $this->query($bounds);

        foreach (self::ENDPOINTS as $endpoint) {
            try {
                $response = $this->httpClient->request('POST', $endpoint, [
                    'headers' => ['User-Agent' => 'Aureum Streetle'],
                    'body' => ['data' => $query],
                    'timeout' => self::TIMEOUT_SECONDS,
                ]);

                if ($response->getStatusCode() !== 200) {
                    continue;
                }

                $elements = $response->toArray(false)['elements'] ?? null;
                if (is_array($elements)) {
                    return array_values(array_filter($elements, is_array(...)));
                }
            } catch (ExceptionInterface) {
                continue;
            }
        }

        throw new StreetleException('OpenStreetMap is busy right now. Please try again in a few minutes.');
    }

    private function query(StreetleBounds $bounds): string
    {
        $box = sprintf('(%F,%F,%F,%F)', $bounds->south, $bounds->west, $bounds->north, $bounds->east);

        return '[out:json][timeout:' . self::TIMEOUT_SECONDS . '];('
            . 'way["highway"]["name"]' . $box . ';'
            . 'way["building"]' . $box . ';'
            . 'way["natural"="water"]' . $box . ';'
            . 'way["waterway"]' . $box . ';'
            . 'way["leisure"~"^(park|garden)$"]' . $box . ';'
            . 'relation["leisure"="park"]["name"]' . $box . ';'
            . 'way["landuse"~"^(grass|village_green)$"]' . $box . ';'
            . 'way["railway"~"^(rail|light_rail|subway|narrow_gauge|tram)$"]' . $box . ';'
            . ');out geom;('
            . 'node["railway"~"^(tram_stop|station)$"]["name"]' . $box . ';'
            . 'nwr["tourism"="museum"]["name"]' . $box . ';'
            . 'nwr["leisure"="park"]["name"]' . $box . ';'
            . 'nwr["name"~"^(' . implode('|', array_map(preg_quote(...), array_keys(StreetleMapBuilder::FEATURED_PLACES))) . ')$"]' . $box . ';'
            . ');out center;';
    }
}
