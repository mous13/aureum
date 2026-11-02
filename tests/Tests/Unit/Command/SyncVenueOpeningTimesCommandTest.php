<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Command;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Command\SyncVenueOpeningTimesCommand;
use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Exception\GooglePlacesException;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Citadel\Aureum\Core\Service\VenueGoogleService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SyncVenueOpeningTimesCommandTest extends TestCase
{
    private GooglePlacesKeyManager&MockObject $keyManager;
    private HotelRepository&MockObject $hotelRepository;
    private VenueGoogleService&MockObject $googleService;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->keyManager = $this->createMock(GooglePlacesKeyManager::class);
        $this->hotelRepository = $this->createMock(HotelRepository::class);
        $this->googleService = $this->createMock(VenueGoogleService::class);

        $this->tester = new CommandTester(new SyncVenueOpeningTimesCommand(
            $this->keyManager,
            $this->hotelRepository,
            $this->googleService,
        ));
    }

    private function hotel(Restaurant|Bar ...$venues): Hotel
    {
        $hotel = new Hotel();
        $hotel->setName('Test Hotel');
        $hotel->setGooglePlacesEnabled(true);
        foreach ($venues as $venue) {
            $collection = $venue instanceof Bar ? $hotel->getBars() : $hotel->getRestaurants();
            $collection->add($venue);
        }

        return $hotel;
    }

    private function linkedBar(string $placeId = 'ChIJbar'): Bar
    {
        $bar = new Bar();
        $bar->setName('Test Bar');
        $bar->setGooglePlaceId($placeId);

        return $bar;
    }

    private function linkedRestaurant(string $placeId = 'ChIJabc'): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant->setName('Test');
        $restaurant->setGooglePlaceId($placeId);

        return $restaurant;
    }

    public function testAbortsWithoutKey(): void
    {
        $this->keyManager->method('hasKey')->willReturn(false);
        $this->hotelRepository->expects(self::never())->method('findBy');

        self::assertSame(Command::FAILURE, $this->tester->execute([]));
    }

    public function testSyncsOnlyEnabledHotelsAndLinkedRestaurants(): void
    {
        $this->keyManager->method('hasKey')->willReturn(true);
        $linked = $this->linkedRestaurant();
        $unlinked = new Restaurant();
        $unlinked->setName('Manual Only');
        $this->hotelRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['googlePlacesEnabled' => true])
            ->willReturn([$this->hotel($linked, $unlinked)]);
        $this->googleService
            ->expects(self::once())
            ->method('sync')
            ->with($linked)
            ->willReturn(true);

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
        self::assertStringContainsString('1 synced', $this->tester->getDisplay());
    }

    public function testContinuesPastFailuresAndExitsNonZero(): void
    {
        $this->keyManager->method('hasKey')->willReturn(true);
        $first = $this->linkedRestaurant('ChIJfail');
        $second = $this->linkedRestaurant('ChIJok');
        $this->hotelRepository->method('findBy')->willReturn([$this->hotel($first, $second)]);
        $this->googleService
            ->method('sync')
            ->willReturnCallback(static function (Restaurant $restaurant): bool {
                if ($restaurant->getGooglePlaceId() === 'ChIJfail') {
                    throw new GooglePlacesException('quota');
                }

                return true;
            });

        self::assertSame(Command::FAILURE, $this->tester->execute([]));
        $display = $this->tester->getDisplay();
        self::assertStringContainsString('1 synced', $display);
        self::assertStringContainsString('1 failed', $display);
    }

    public function testDryRunDoesNotSync(): void
    {
        $this->keyManager->method('hasKey')->willReturn(true);
        $this->hotelRepository->method('findBy')->willReturn([$this->hotel($this->linkedRestaurant())]);
        $this->googleService->expects(self::never())->method('sync');

        self::assertSame(Command::SUCCESS, $this->tester->execute(['--dry-run' => true]));
    }

    public function testContinuesPastNonGoogleExceptionsAndExitsNonZero(): void
    {
        $this->keyManager->method('hasKey')->willReturn(true);
        $first = $this->linkedRestaurant('ChIJfail');
        $second = $this->linkedRestaurant('ChIJok');
        $this->hotelRepository->method('findBy')->willReturn([$this->hotel($first, $second)]);
        $this->googleService
            ->method('sync')
            ->willReturnCallback(static function (Restaurant $restaurant): bool {
                if ($restaurant->getGooglePlaceId() === 'ChIJfail') {
                    throw new \RuntimeException('database error');
                }

                return true;
            });

        self::assertSame(Command::FAILURE, $this->tester->execute([]));
        $display = $this->tester->getDisplay();
        self::assertStringContainsString('1 synced', $display);
        self::assertStringContainsString('1 failed', $display);
    }

    public function testSyncsLinkedBarsAlongsideRestaurants(): void
    {
        $this->keyManager->method('hasKey')->willReturn(true);
        $restaurant = $this->linkedRestaurant();
        $bar = $this->linkedBar();
        $unlinkedBar = new Bar();
        $unlinkedBar->setName('Manual Only Bar');
        $this->hotelRepository->method('findBy')->willReturn([$this->hotel($restaurant, $bar, $unlinkedBar)]);

        $synced = [];
        $this->googleService
            ->expects(self::exactly(2))
            ->method('sync')
            ->willReturnCallback(static function (Restaurant|Bar $venue) use (&$synced): bool {
                $synced[] = $venue;

                return true;
            });

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
        self::assertSame([$restaurant, $bar], $synced);
        self::assertStringContainsString('2 synced', $this->tester->getDisplay());
    }
}
