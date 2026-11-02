<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Entity\VenueInterface;
use Citadel\Aureum\Core\Repository\BarRepository;
use Citadel\Aureum\Core\Repository\RestaurantRepository;
use Citadel\Aureum\Core\Service\GoogleOpeningHoursTranslator;
use Citadel\Aureum\Core\Service\GooglePlacesClient;
use Citadel\Aureum\Core\Service\VenueGoogleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VenueGoogleServiceTest extends TestCase
{
    private GooglePlacesClient&MockObject $client;
    private RestaurantRepository&MockObject $restaurantRepository;
    private BarRepository&MockObject $barRepository;
    private VenueGoogleService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(GooglePlacesClient::class);
        $this->restaurantRepository = $this->createMock(RestaurantRepository::class);
        $this->barRepository = $this->createMock(BarRepository::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnMap([
            [Restaurant::class, $this->restaurantRepository],
            [Bar::class, $this->barRepository],
        ]);

        $this->service = new VenueGoogleService(
            $this->client,
            new GoogleOpeningHoursTranslator(),
            $entityManager,
        );
    }

    public static function venueTypes(): array
    {
        return [
            'restaurant' => [Restaurant::class],
            'bar' => [Bar::class],
        ];
    }

    private function venue(string $class): VenueInterface
    {
        $venue = new $class();
        $venue->setName('Scarpetta');
        $venue->setStreet('88 Madison Ave');
        $venue->setNeighbourhood('NoMad');

        return $venue;
    }

    private function repositoryFor(VenueInterface $venue): MockObject
    {
        return $venue instanceof Bar ? $this->barRepository : $this->restaurantRepository;
    }

    private function mondayHours(): array
    {
        return [
            'periods' => [
                ['open' => ['day' => 1, 'hour' => 9, 'minute' => 0], 'close' => ['day' => 1, 'hour' => 17, 'minute' => 0]],
            ],
        ];
    }

    #[DataProvider('venueTypes')]
    public function testSearchBuildsQueryFromVenue(string $class): void
    {
        $this->client
            ->expects(self::once())
            ->method('searchText')
            ->with('Scarpetta, 88 Madison Ave, NoMad')
            ->willReturn([['id' => 'ChIJabc', 'name' => 'Scarpetta', 'address' => '88 Madison Ave']]);

        $result = $this->service->search($this->venue($class));

        self::assertCount(1, $result);
    }

    #[DataProvider('venueTypes')]
    public function testLinkStoresPlaceIdAndHours(string $class): void
    {
        $venue = $this->venue($class);
        $this->client->method('getOpeningHours')->willReturn($this->mondayHours());
        $this->repositoryFor($venue)->expects(self::once())->method('save')->with($venue);

        $times = $this->service->link($venue, 'ChIJabc');

        self::assertSame('ChIJabc', $venue->getGooglePlaceId());
        self::assertNotNull($venue->getOpeningTimesSyncedAt());
        self::assertSame([['09:00', '17:00']], $times['mon']['ranges']);
        self::assertSame($times, $venue->getOpeningTimes());
    }

    #[DataProvider('venueTypes')]
    public function testLinkWithoutHoursKeepsExistingTimes(string $class): void
    {
        $venue = $this->venue($class);
        $existing = ['mon' => ['closed' => true, 'ranges' => []]];
        $venue->setOpeningTimes($existing);
        $this->client->method('getOpeningHours')->willReturn(null);
        $this->repositoryFor($venue)->expects(self::once())->method('save');

        $times = $this->service->link($venue, 'ChIJabc');

        self::assertNull($times);
        self::assertSame('ChIJabc', $venue->getGooglePlaceId());
        self::assertSame($existing, $venue->getOpeningTimes());
        self::assertNull($venue->getOpeningTimesSyncedAt());
    }

    #[DataProvider('venueTypes')]
    public function testUnlinkClearsPlaceIdOnly(string $class): void
    {
        $venue = $this->venue($class);
        $venue->setGooglePlaceId('ChIJabc');
        $times = ['mon' => ['closed' => true, 'ranges' => []]];
        $venue->setOpeningTimes($times);
        $this->repositoryFor($venue)->expects(self::once())->method('save');

        $this->service->unlink($venue);

        self::assertNull($venue->getGooglePlaceId());
        self::assertSame($times, $venue->getOpeningTimes());
    }

    #[DataProvider('venueTypes')]
    public function testSyncUpdatesLinkedVenue(string $class): void
    {
        $venue = $this->venue($class);
        $venue->setGooglePlaceId('ChIJabc');
        $this->client->method('getOpeningHours')->willReturn($this->mondayHours());
        $this->repositoryFor($venue)->expects(self::once())->method('save');

        self::assertTrue($this->service->sync($venue));
        self::assertNotNull($venue->getOpeningTimesSyncedAt());
    }

    #[DataProvider('venueTypes')]
    public function testSyncReturnsFalseWhenNoHours(string $class): void
    {
        $venue = $this->venue($class);
        $venue->setGooglePlaceId('ChIJabc');
        $this->client->method('getOpeningHours')->willReturn(null);
        $this->repositoryFor($venue)->expects(self::never())->method('save');

        self::assertFalse($this->service->sync($venue));
    }
}
