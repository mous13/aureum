<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Entity\Enum\DressCode;
use Citadel\Aureum\Core\Entity\Enum\EntryPolicy;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Event;
use Citadel\Aureum\Core\Entity\VenueInterface;
use PHPUnit\Framework\TestCase;

class BarTest extends TestCase
{
    public function testDefaults(): void
    {
        $bar = new Bar();

        self::assertInstanceOf(VenueInterface::class, $bar);
        self::assertNull($bar->getDressCode());
        self::assertNull($bar->getEntryPolicy());
        self::assertNull($bar->getHappyHour());
        self::assertNull($bar->getOpeningTimes());
        self::assertNull($bar->getGooglePlaceId());
        self::assertNull($bar->getOpeningTimesSyncedAt());
        self::assertCount(0, $bar->getStyles());
        self::assertCount(0, $bar->getTags());
        self::assertCount(0, $bar->getConnections());
    }

    public function testBarDetailsRoundTrip(): void
    {
        $bar = new Bar();
        $bar->setDressCode(DressCode::SMART);
        $bar->setEntryPolicy(EntryPolicy::BOTH);
        $bar->setHappyHour('Daily 4–6pm');
        $times = ['fri' => ['closed' => false, 'ranges' => [['17:00', '02:00']]]];
        $bar->setOpeningTimes($times);

        self::assertSame(DressCode::SMART, $bar->getDressCode());
        self::assertSame(EntryPolicy::BOTH, $bar->getEntryPolicy());
        self::assertSame('Daily 4–6pm', $bar->getHappyHour());
        self::assertSame($times, $bar->getOpeningTimes());
    }

    public function testEnumLabels(): void
    {
        self::assertSame('Smart Casual', DressCode::SMART_CASUAL->getLabel());
        self::assertSame('Reservations only', EntryPolicy::RESERVATIONS->getLabel());
        self::assertSame('Bars', Module::BARS->getLabel());
        self::assertSame('aureum.module.bars.manage', Module::BARS->permission('manage'));
    }

    public function testEventCanLinkToBar(): void
    {
        $event = new Event();
        self::assertNull($event->getBar());

        $bar = new Bar();
        $event->setBar($bar);

        self::assertSame($bar, $event->getBar());
    }
}
