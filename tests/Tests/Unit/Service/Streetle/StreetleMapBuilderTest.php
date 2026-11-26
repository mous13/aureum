<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service\Streetle;

use Citadel\Aureum\Core\Entity\Enum\StreetKind;
use Citadel\Aureum\Core\Service\Streetle\StreetleBounds;
use Citadel\Aureum\Core\Service\Streetle\StreetleMapBuilder;
use Citadel\Aureum\Core\Service\Streetle\StreetNameNormalizer;
use PHPUnit\Framework\TestCase;

class StreetleMapBuilderTest extends TestCase
{
    private StreetleMapBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new StreetleMapBuilder(new StreetNameNormalizer());
    }

    public function testNamedWaysWithTheSameNameBecomeOneStreet(): void
    {
        $data = $this->builder->build([
            $this->way(['highway' => 'primary', 'name' => 'Deansgate'], [[53.48, -2.25], [53.481, -2.251]]),
            $this->way(['highway' => 'primary', 'name' => 'Deansgate'], [[53.481, -2.251], [53.482, -2.252]]),
            $this->way(['highway' => 'residential', 'name' => 'Peter Street'], [[53.477, -2.248], [53.478, -2.246]]),
        ], $this->area());

        self::assertCount(2, $data->streets);
        $deansgate = $data->streets[0];
        self::assertSame('Deansgate', $deansgate->name);
        self::assertSame('deansgate', $deansgate->normalizedName);
        self::assertSame(StreetKind::MAJOR, $deansgate->kind);
        self::assertSame([[[-2.25, 53.48], [-2.251, 53.481], [-2.252, 53.482]]], $deansgate->lines);
    }

    public function testConnectedPiecesAreJoinedWhicheverWayTheyRun(): void
    {
        $data = $this->builder->build([
            $this->way(['highway' => 'residential', 'name' => 'Oxford Street'], [[53.47, -2.24], [53.471, -2.241]]),
            $this->way(['highway' => 'residential', 'name' => 'Oxford Street'], [[53.473, -2.243], [53.472, -2.242]]),
            $this->way(['highway' => 'residential', 'name' => 'Oxford Street'], [[53.472, -2.242], [53.471, -2.241]]),
            $this->way(['highway' => 'residential', 'name' => 'Oxford Street'], [[53.48, -2.23], [53.481, -2.231]]),
        ], $this->area());

        self::assertSame([
            [[-2.24, 53.47], [-2.241, 53.471], [-2.242, 53.472], [-2.243, 53.473]],
            [[-2.23, 53.48], [-2.231, 53.481]],
        ], $data->streets[0]->lines);
    }

    public function testAStreetTakesItsMostProminentKind(): void
    {
        $data = $this->builder->build([
            $this->way(['highway' => 'footway', 'name' => 'Market Street'], [[53.4, -2.2], [53.41, -2.21]]),
            $this->way(['highway' => 'secondary', 'name' => 'Market Street'], [[53.41, -2.21], [53.42, -2.22]]),
        ], $this->area());

        self::assertSame(StreetKind::MAJOR, $data->streets[0]->kind);
    }

    public function testUnnamedAndNonStreetHighwaysAreLeftOut(): void
    {
        $data = $this->builder->build([
            $this->way(['highway' => 'service'], [[53.4, -2.2], [53.41, -2.21]]),
            $this->way(['highway' => 'platform', 'name' => 'Platform 1'], [[53.4, -2.2], [53.41, -2.21]]),
            $this->way(['highway' => 'construction', 'name' => 'New Road'], [[53.4, -2.2], [53.41, -2.21]]),
            $this->way(['highway' => 'footway', 'name' => 'Barton Arcade'], [[53.4, -2.2], [53.41, -2.21]]),
        ], $this->area());

        self::assertCount(1, $data->streets);
        self::assertSame('Barton Arcade', $data->streets[0]->name);
        self::assertSame(StreetKind::PATH, $data->streets[0]->kind);
    }

    public function testCoordinatesAreRoundedToFiveDecimals(): void
    {
        $data = $this->builder->build([
            $this->way(['highway' => 'residential', 'name' => 'Quay Street'], [[53.4791234, -2.2512345], [53.47, -2.25]]),
        ], $this->area());

        self::assertSame([-2.25123, 53.47912], $data->streets[0]->lines[0][0]);
    }

    public function testBasemapLayersAreSorted(): void
    {
        $ring = [[53.4, -2.2], [53.41, -2.2], [53.41, -2.21], [53.4, -2.2]];
        $line = [[53.4, -2.2], [53.41, -2.21]];

        $data = $this->builder->build([
            $this->way(['building' => 'yes'], $ring),
            $this->way(['leisure' => 'park', 'name' => 'Parsonage Gardens'], $ring),
            $this->way(['natural' => 'water'], $ring),
            $this->way(['waterway' => 'river', 'name' => 'River Irwell'], $line),
            $this->way(['railway' => 'rail'], $line),
            $this->way(['railway' => 'tram'], $line),
            $this->way(['railway' => 'abandoned'], $line),
        ], $this->area());

        self::assertSame([], $data->streets);
        self::assertCount(1, $data->basemap['buildings']);
        self::assertCount(1, $data->basemap['parks']);
        self::assertCount(1, $data->basemap['water']);
        self::assertCount(1, $data->basemap['waterways']);
        self::assertCount(1, $data->basemap['rail']);
        self::assertCount(1, $data->basemap['tram']);
    }

    public function testOpenWaysAreNotTreatedAsAreas(): void
    {
        $data = $this->builder->build([
            $this->way(['building' => 'yes'], [[53.4, -2.2], [53.41, -2.2], [53.41, -2.21]]),
        ], $this->area());

        self::assertSame([], $data->basemap['buildings']);
    }

    public function testOnlyTheChosenPlacesAreKept(): void
    {
        $data = $this->builder->build([
            $this->node(['railway' => 'tram_stop', 'name' => 'St Peter\'s Square'], 53.4781, -2.2433),
            $this->node(['railway' => 'tram_stop', 'name' => 'St Peter\'s Square'], 53.4782, -2.2434),
            $this->centred('way', ['shop' => 'mall', 'name' => 'Manchester Arndale'], 53.4835, -2.2397),
            $this->centred('relation', ['shop' => 'mall', 'name' => 'Afflecks'], 53.4829, -2.2364),
            $this->centred('way', ['tourism' => 'gallery', 'name' => 'Manchester Art Gallery'], 53.4787, -2.2413),
            $this->centred('relation', ['tourism' => 'museum', 'name' => 'People\'s History Museum'], 53.4816, -2.2534),
            $this->centred('way', ['leisure' => 'park', 'name' => 'Sackville Gardens'], 53.4747, -2.2364),
            $this->centred('way', ['shop' => 'mall', 'name' => 'Urban Exchange'], 53.4870, -2.2330),
            $this->node(['tourism' => 'gallery', 'name' => 'Whitewall Galleries'], 53.481, -2.245),
            $this->node(['railway' => 'station', 'name' => 'Manchester Victoria'], 53.4875, -2.2425),
            $this->node(['amenity' => 'cafe', 'name' => 'Some Cafe'], 53.48, -2.24),
            $this->centred('way', ['leisure' => 'park'], 53.47, -2.25),
        ], $this->area());

        self::assertSame([
            ['name' => null, 'kind' => 'tram', 'at' => [-2.2433, 53.4781]],
            ['name' => 'Manchester Arndale', 'kind' => 'shopping', 'at' => [-2.2397, 53.4835]],
            ['name' => 'Afflecks', 'kind' => 'shopping', 'at' => [-2.2364, 53.4829]],
            ['name' => 'Manchester Art Gallery', 'kind' => 'gallery', 'at' => [-2.2413, 53.4787]],
            ['name' => 'People\'s History Museum', 'kind' => 'museum', 'at' => [-2.2534, 53.4816]],
            ['name' => 'Sackville Gardens', 'kind' => 'park', 'at' => [-2.2364, 53.4747]],
        ], $data->basemap['places']);
    }

    public function testDifferentTramStopsAreKeptSeparately(): void
    {
        $data = $this->builder->build([
            $this->node(['railway' => 'tram_stop', 'name' => 'Victoria'], 53.4875, -2.2425),
            $this->node(['railway' => 'tram_stop', 'name' => 'Shudehill'], 53.4852, -2.2391),
        ], $this->area());

        self::assertCount(2, $data->basemap['places']);
    }

    public function testStreetsOutsideThePlayAreaAreLeftOut(): void
    {
        $data = $this->builder->build([
            $this->way(['highway' => 'residential', 'name' => 'Far Away Road'], [[52.0, -1.0], [52.01, -1.01]]),
            $this->way(['highway' => 'residential', 'name' => 'Edge Street'], [[52.0, -1.0], [53.45, -2.25]]),
        ], $this->area());

        self::assertCount(1, $data->streets);
        self::assertSame('Edge Street', $data->streets[0]->name);
    }

    private function area(): StreetleBounds
    {
        return new StreetleBounds(-2.3, 53.39, -2.19, 53.5);
    }

    /**
     * @param array<string, string> $tags
     * @param list<array{float, float}> $points lat, lon pairs
     * @return array<string, mixed>
     */
    private function way(array $tags, array $points): array
    {
        return [
            'type' => 'way',
            'tags' => $tags,
            'geometry' => array_map(static fn (array $p): array => ['lat' => $p[0], 'lon' => $p[1]], $points),
        ];
    }

    /**
     * @param array<string, string> $tags
     * @return array<string, mixed>
     */
    private function centred(string $type, array $tags, float $lat, float $lon): array
    {
        return ['type' => $type, 'tags' => $tags, 'center' => ['lat' => $lat, 'lon' => $lon]];
    }

    /**
     * @param array<string, string> $tags
     * @return array<string, mixed>
     */
    private function node(array $tags, float $lat, float $lon): array
    {
        return ['type' => 'node', 'tags' => $tags, 'lat' => $lat, 'lon' => $lon];
    }
}
