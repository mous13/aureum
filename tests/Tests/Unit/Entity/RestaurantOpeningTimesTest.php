<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Restaurant;
use PHPUnit\Framework\TestCase;

class RestaurantOpeningTimesTest extends TestCase
{
    public function testOpeningTimesDefaultsToNull(): void
    {
        $restaurant = new Restaurant();

        self::assertNull($restaurant->getOpeningTimes());
        self::assertNull($restaurant->getGooglePlaceId());
        self::assertNull($restaurant->getOpeningTimesSyncedAt());
    }

    public function testOpeningTimesRoundTrip(): void
    {
        $restaurant = new Restaurant();
        $times = [
            'mon' => ['closed' => false, 'ranges' => [['12:00', '14:30']]],
            'tue' => ['closed' => true, 'ranges' => []],
        ];

        $restaurant->setOpeningTimes($times);
        $restaurant->setGooglePlaceId('ChIJabc123');
        $syncedAt = new \DateTime('2026-09-01 12:00:00');
        $restaurant->setOpeningTimesSyncedAt($syncedAt);

        self::assertSame($times, $restaurant->getOpeningTimes());
        self::assertSame('ChIJabc123', $restaurant->getGooglePlaceId());
        self::assertSame($syncedAt, $restaurant->getOpeningTimesSyncedAt());
    }

    public function testHotelGooglePlacesDefaults(): void
    {
        $hotel = new Hotel();

        self::assertNull($hotel->getTimezone());
        self::assertFalse($hotel->isGooglePlacesEnabled());

        $hotel->setTimezone('Europe/London');
        $hotel->setGooglePlacesEnabled(true);

        self::assertSame('Europe/London', $hotel->getTimezone());
        self::assertTrue($hotel->isGooglePlacesEnabled());
    }
}
