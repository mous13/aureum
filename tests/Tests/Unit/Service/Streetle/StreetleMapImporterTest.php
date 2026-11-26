<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service\Streetle;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\StreetleMap;
use Citadel\Aureum\Core\Entity\StreetleSession;
use Citadel\Aureum\Core\Entity\StreetleStreet;
use Citadel\Aureum\Core\Exception\StreetleException;
use Citadel\Aureum\Core\Repository\StreetleMapRepository;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;
use Citadel\Aureum\Core\Repository\StreetleStreetRepository;
use Citadel\Aureum\Core\Service\Streetle\OverpassClient;
use Citadel\Aureum\Core\Service\Streetle\StreetleBounds;
use Citadel\Aureum\Core\Service\Streetle\StreetleMapBuilder;
use Citadel\Aureum\Core\Service\Streetle\StreetleMapImporter;
use Citadel\Aureum\Core\Service\Streetle\StreetNameNormalizer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StreetleMapImporterTest extends TestCase
{
    private Hotel $hotel;
    private OverpassClient&MockObject $overpass;
    private StreetleMapRepository&MockObject $maps;
    private StreetleStreetRepository&MockObject $streets;
    private StreetleSessionRepository&MockObject $sessions;
    private StreetleMapImporter $importer;

    protected function setUp(): void
    {
        $this->hotel = new Hotel();
        $this->overpass = $this->createMock(OverpassClient::class);
        $this->maps = $this->createMock(StreetleMapRepository::class);
        $this->streets = $this->createMock(StreetleStreetRepository::class);
        $this->sessions = $this->createMock(StreetleSessionRepository::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(static fn (callable $work): mixed => $work());

        $this->importer = new StreetleMapImporter(
            $this->overpass,
            new StreetleMapBuilder(new StreetNameNormalizer()),
            $this->maps,
            $this->streets,
            $this->sessions,
            $entityManager,
        );
    }

    public function testAFirstImportCreatesTheMapAndItsStreets(): void
    {
        $this->overpass->method('fetchArea')->willReturn([$this->deansgate()]);
        $this->streets->expects(self::never())->method('deleteForMap');
        $this->streets->expects(self::once())->method('saveAll')->with(self::callback(
            static fn (array $saved): bool => count($saved) === 1 && $saved[0] instanceof StreetleStreet && $saved[0]->getName() === 'Deansgate',
        ));

        $map = $this->importer->import($this->hotel, StreetleBounds::manchesterCityCentre());

        self::assertSame(1, $map->getStreetCount());
        self::assertSame($this->hotel, $map->getHotel());
    }

    public function testRefreshingReplacesStreetsAndClosesOpenSessions(): void
    {
        $existing = new StreetleMap($this->hotel, StreetleBounds::manchesterCityCentre());
        $employee = new Employee();
        $employee->setHotel($this->hotel);
        $open = new StreetleSession($employee, StreetleMode::FREE_PLAY, new DateTimeImmutable('-10 minutes'));

        $this->overpass->method('fetchArea')->willReturn([$this->deansgate()]);
        $this->maps->method('findOneByHotel')->willReturn($existing);
        $this->sessions->method('findOpenForHotel')->willReturn([$open]);
        $this->streets->expects(self::once())->method('deleteForMap')->with($existing);

        self::assertSame($existing, $this->importer->import($this->hotel, StreetleBounds::manchesterCityCentre()));
        self::assertFalse($open->isOpen());
    }

    public function testAnAreaWithoutStreetsIsRefused(): void
    {
        $this->overpass->method('fetchArea')->willReturn([]);
        $this->maps->expects(self::never())->method('save');

        $this->expectException(StreetleException::class);
        $this->importer->import($this->hotel, StreetleBounds::manchesterCityCentre());
    }

    /** @return array<string, mixed> */
    private function deansgate(): array
    {
        return [
            'type' => 'way',
            'tags' => ['highway' => 'primary', 'name' => 'Deansgate'],
            'geometry' => [['lat' => 53.48, 'lon' => -2.248], ['lat' => 53.481, 'lon' => -2.2485]],
        ];
    }
}
