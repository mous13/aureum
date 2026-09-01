<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Repository\RestaurantRepository;
use Citadel\Aureum\Core\Service\GoogleOpeningHoursTranslator;
use Citadel\Aureum\Core\Service\GooglePlacesClient;
use Citadel\Aureum\Core\Service\RestaurantGoogleService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestaurantGoogleServiceTest extends TestCase
{
    private GooglePlacesClient&MockObject $client;
    private RestaurantRepository&MockObject $repository;
    private RestaurantGoogleService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(GooglePlacesClient::class);
        $this->repository = $this->createMock(RestaurantRepository::class);
        $this->service = new RestaurantGoogleService(
            $this->client,
            new GoogleOpeningHoursTranslator(),
            $this->repository,
        );
    }

    private function restaurant(): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant->setName('Scarpetta');
        $restaurant->setStreet('88 Madison Ave');
        $restaurant->setNeighbourhood('NoMad');

        return $restaurant;
    }

    public function testSearchBuildsQueryFromRestaurant(): void
    {
        $this->client
            ->expects(self::once())
            ->method('searchText')
            ->with('Scarpetta, 88 Madison Ave, NoMad')
            ->willReturn([['id' => 'ChIJabc', 'name' => 'Scarpetta', 'address' => '88 Madison Ave']]);

        $result = $this->service->search($this->restaurant());

        self::assertCount(1, $result);
    }

    public function testLinkStoresPlaceIdAndHours(): void
    {
        $restaurant = $this->restaurant();
        $this->client->method('getOpeningHours')->willReturn([
            'periods' => [
                ['open' => ['day' => 1, 'hour' => 9, 'minute' => 0], 'close' => ['day' => 1, 'hour' => 17, 'minute' => 0]],
            ],
        ]);
        $this->repository->expects(self::once())->method('save')->with($restaurant);

        $times = $this->service->link($restaurant, 'ChIJabc');

        self::assertSame('ChIJabc', $restaurant->getGooglePlaceId());
        self::assertNotNull($restaurant->getOpeningTimesSyncedAt());
        self::assertSame([['09:00', '17:00']], $times['mon']['ranges']);
        self::assertSame($times, $restaurant->getOpeningTimes());
    }

    public function testLinkWithoutHoursKeepsExistingTimes(): void
    {
        $restaurant = $this->restaurant();
        $existing = ['mon' => ['closed' => true, 'ranges' => []]];
        $restaurant->setOpeningTimes($existing);
        $this->client->method('getOpeningHours')->willReturn(null);
        $this->repository->expects(self::once())->method('save');

        $times = $this->service->link($restaurant, 'ChIJabc');

        self::assertNull($times);
        self::assertSame('ChIJabc', $restaurant->getGooglePlaceId());
        self::assertSame($existing, $restaurant->getOpeningTimes());
        self::assertNull($restaurant->getOpeningTimesSyncedAt());
    }

    public function testUnlinkClearsPlaceIdOnly(): void
    {
        $restaurant = $this->restaurant();
        $restaurant->setGooglePlaceId('ChIJabc');
        $times = ['mon' => ['closed' => true, 'ranges' => []]];
        $restaurant->setOpeningTimes($times);
        $this->repository->expects(self::once())->method('save');

        $this->service->unlink($restaurant);

        self::assertNull($restaurant->getGooglePlaceId());
        self::assertSame($times, $restaurant->getOpeningTimes());
    }

    public function testSyncUpdatesLinkedRestaurant(): void
    {
        $restaurant = $this->restaurant();
        $restaurant->setGooglePlaceId('ChIJabc');
        $this->client->method('getOpeningHours')->willReturn([
            'periods' => [
                ['open' => ['day' => 1, 'hour' => 9, 'minute' => 0], 'close' => ['day' => 1, 'hour' => 17, 'minute' => 0]],
            ],
        ]);
        $this->repository->expects(self::once())->method('save');

        self::assertTrue($this->service->sync($restaurant));
        self::assertNotNull($restaurant->getOpeningTimesSyncedAt());
    }

    public function testSyncReturnsFalseWhenNoHours(): void
    {
        $restaurant = $this->restaurant();
        $restaurant->setGooglePlaceId('ChIJabc');
        $this->client->method('getOpeningHours')->willReturn(null);
        $this->repository->expects(self::never())->method('save');

        self::assertFalse($this->service->sync($restaurant));
    }
}
