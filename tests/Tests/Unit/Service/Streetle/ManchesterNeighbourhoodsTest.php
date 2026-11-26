<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service\Streetle;

use Citadel\Aureum\Core\Service\Streetle\ManchesterNeighbourhoods;
use Citadel\Aureum\Core\Service\Streetle\StreetleBounds;
use PHPUnit\Framework\TestCase;

class ManchesterNeighbourhoodsTest extends TestCase
{
    public function testItMatchesTheVisitManchesterGuestMap(): void
    {
        $names = array_column(ManchesterNeighbourhoods::ALL, 'name');
        sort($names);

        self::assertSame([
            'Ancoats', 'Castlefield', 'Central Retail District', 'Chinatown', 'Civic Quarter', 'First Street',
            'Greengate', 'Kampus', 'Mayfield', 'Medieval Quarter', 'NOMA', 'New Bailey', 'Northern Quarter',
            'Oxford Road Corridor', 'Petersfield', 'Piccadilly', 'Spinningfields', 'St. John\'s', 'The Gay Village',
        ], $names);
    }

    public function testEveryNeighbourhoodHasAColourAndAnAreaNearTheCityCentre(): void
    {
        $nearby = StreetleBounds::manchesterCityCentre()->padded(0.01);

        foreach (ManchesterNeighbourhoods::ALL as $neighbourhood) {
            self::assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $neighbourhood['colour']);
            self::assertNotEmpty($neighbourhood['polygons']);
            foreach ($neighbourhood['polygons'] as $ring) {
                self::assertGreaterThanOrEqual(4, count($ring), $neighbourhood['name']);
                self::assertTrue($nearby->touches([$ring]), $neighbourhood['name']);
            }
        }
    }
}
