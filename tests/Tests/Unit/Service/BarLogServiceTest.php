<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Entity\BarLog;
use Citadel\Aureum\Core\Entity\BarStyle;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\DressCode;
use Citadel\Aureum\Core\Entity\Enum\EntryPolicy;
use Citadel\Aureum\Core\Entity\Enum\LogAction;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Tag;
use Citadel\Aureum\Core\Repository\BarLogRepository;
use Citadel\Aureum\Core\Service\BarLogService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BarLogServiceTest extends TestCase
{
    private BarLogRepository&MockObject $repository;
    private BarLogService $service;
    private Employee $employee;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(BarLogRepository::class);
        $this->service = new BarLogService($this->repository);
        $this->employee = new Employee();
        $this->employee->setName('Concierge');
    }

    private function bar(): Bar
    {
        $hotel = new Hotel();
        $hotel->setName('Test Hotel');

        $style = new BarStyle();
        $style->setName('Cocktail');

        $tag = new Tag();
        $tag->setName('Rooftop');

        $bar = new Bar();
        $bar->setHotel($hotel);
        $bar->setName('Bemelmans');
        $bar->setNeighbourhood('Upper East Side');
        $bar->setStreet('35 E 76th St');
        $bar->setDressCode(DressCode::SMART_CASUAL);
        $bar->setEntryPolicy(EntryPolicy::WALK_INS);
        $bar->getStyles()->add($style);
        $bar->getTags()->add($tag);

        return $bar;
    }

    public function testLogCreatedRecordsFilledFields(): void
    {
        $bar = $this->bar();
        $saved = null;
        $this->repository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (BarLog $log) use (&$saved): void {
                $saved = $log;
            });

        $this->service->logCreated($bar, $this->employee);

        self::assertSame(LogAction::CREATED, $saved->getAction());
        self::assertSame($bar, $saved->getBar());
        self::assertSame($bar->getHotel(), $saved->getHotel());
        self::assertSame($this->employee, $saved->getPerformedBy());
        self::assertSame([
            'name' => 'Bemelmans',
            'neighbourhood' => 'Upper East Side',
            'street' => '35 E 76th St',
            'styles' => ['Cocktail'],
            'dressCode' => 'Smart Casual',
            'entryPolicy' => 'Walk-ins only',
            'tags' => ['Rooftop'],
        ], $saved->getChanges());
    }

    public function testLogUpdatedRecordsOnlyChangedFields(): void
    {
        $bar = $this->bar();
        $original = $this->service->captureCurrentState($bar);

        $bar->setDressCode(DressCode::FORMAL);
        $bar->setHappyHour('Mon–Fri 5–7pm');
        $bar->getTags()->clear();

        $saved = null;
        $this->repository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (BarLog $log) use (&$saved): void {
                $saved = $log;
            });

        $this->service->logUpdated($bar, $original, $this->employee);

        self::assertSame(LogAction::UPDATED, $saved->getAction());
        self::assertSame([
            'dressCode' => ['old' => 'Smart Casual', 'new' => 'Formal'],
            'happyHour' => ['old' => null, 'new' => 'Mon–Fri 5–7pm'],
            'tags' => ['old' => ['Rooftop'], 'new' => []],
        ], $saved->getChanges());
    }

    public function testLogUpdatedSkipsWhenNothingChanged(): void
    {
        $bar = $this->bar();
        $original = $this->service->captureCurrentState($bar);
        $this->repository->expects(self::never())->method('save');

        $this->service->logUpdated($bar, $original, $this->employee);
    }
}
