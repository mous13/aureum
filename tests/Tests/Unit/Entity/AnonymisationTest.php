<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\AmenityCard;
use Citadel\Aureum\Core\Entity\AnonymisableInterface;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Entity\Package;
use Citadel\Aureum\Core\Entity\Booking;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnonymisationTest extends TestCase
{
    public function testBookingLosesEveryGuestIdentifier(): void
    {
        $booking = new Booking();
        $booking->setGuest('Sam Whitfield');
        $booking->setNumber('07700 900001');
        $booking->setEmail('sam@example.test');
        $booking->setDetails([
            'pickup' => '12 Old Street, London',
            'dropoff' => 'Heathrow T5',
        ]);
        $booking->setNotes('guest asked for a quiet driver');
        $booking->setCost('45.00');

        $booking->anonymise();

        self::assertNull($booking->getGuest());
        self::assertNull($booking->getNumber());
        self::assertNull($booking->getEmail());
        self::assertSame([], $booking->getDetails());
        self::assertNull($booking->getNotes());
        self::assertTrue($booking->isAnonymised());

        self::assertSame('45.00', $booking->getCost());
    }

    public function testFineKeepsItsReferenceButLosesTheGuest(): void
    {
        $fine = new Fine();
        $fine->setNumber('FINE-2026-0031');
        $fine->setName('Sam Whitfield');
        $fine->setEmail('sam@example.test');
        $fine->setNote('smoking in a non-smoking room');

        $fine->anonymise();

        self::assertSame(Fine::ANONYMISED_PLACEHOLDER, $fine->getName());
        self::assertNull($fine->getEmail());
        self::assertNull($fine->getNote());
        self::assertTrue($fine->isAnonymised());

        self::assertSame('FINE-2026-0031', $fine->getNumber());
    }

    public function testLostPropertyKeepsTheItemButLosesTheGuest(): void
    {
        $item = new LostProperty();
        $item->setGuest('Sam Whitfield');
        $item->setContact('sam@example.test');
        $item->setNote('will collect on Friday');
        $item->setDescription('black umbrella');
        $item->setLocation('Lobby');

        $item->anonymise();

        self::assertNull($item->getGuest());
        self::assertNull($item->getContact());
        self::assertNull($item->getNote());
        self::assertTrue($item->isAnonymised());
        self::assertSame('black umbrella', $item->getDescription());
        self::assertSame('Lobby', $item->getLocation());
    }

    public function testPackageLosesRecipientAndNote(): void
    {
        $package = new Package();
        $package->setName('Sam Whitfield');
        $package->setDescription('small box');
        $package->setLocation('Back office');
        $package->setNote('friend collecting on their behalf');

        $package->anonymise();

        self::assertSame(Package::ANONYMISED_PLACEHOLDER, $package->getName());
        self::assertNull($package->getNote());
        self::assertTrue($package->isAnonymised());
        self::assertSame('small box', $package->getDescription());
    }

    public function testAmenityCardLosesTheGuestButKeepsTheDelivery(): void
    {
        $card = new AmenityCard();
        $card->setRoomNumber('412');
        $card->setGuestLastName('Whitfield');
        $card->setItemsText("2x Beer\n1x Sweets");

        $card->anonymise();

        self::assertNull($card->getGuestLastName());
        self::assertTrue($card->isAnonymised());
        self::assertSame('412', $card->getRoomNumber());
        self::assertSame([
            ['label' => '2x Beer', 'done' => false, 'priority' => false],
            ['label' => '1x Sweets', 'done' => false, 'priority' => false],
        ], $card->getItems());
    }

    #[DataProvider('anonymisableProvider')]
    public function testNotAnonymisedUntilAnonymiseIsCalled(callable $factory): void
    {
        /** @var AnonymisableInterface $record */
        $record = $factory();

        self::assertFalse($record->isAnonymised());
    }

    #[DataProvider('anonymisableProvider')]
    public function testAnonymisingTwiceIsHarmless(callable $factory): void
    {
        /** @var AnonymisableInterface $record */
        $record = $factory();

        $record->anonymise();
        $first = $record->getAnonymisedAt();

        $record->anonymise();

        self::assertTrue($record->isAnonymised());
        self::assertNotNull($first);
    }

    #[DataProvider('moduleProvider')]
    public function testEachRecordReportsItsModule(string $class, Module $expected): void
    {
        self::assertSame($expected, $class::getModule());
    }

    public static function anonymisableProvider(): array
    {
        return [
            'booking' => [static fn (): Booking => new Booking()],
            'fine' => [static fn (): Fine => new Fine()],
            'lost property' => [static fn (): LostProperty => new LostProperty()],
            'package' => [static fn (): Package => new Package()],
            'amenity card' => [static fn (): AmenityCard => new AmenityCard()],
        ];
    }

    public static function moduleProvider(): array
    {
        return [
            [Booking::class, Module::BOOKINGS],
            [Fine::class, Module::FINES],
            [LostProperty::class, Module::LOST_PROPERTY],
            [Package::class, Module::PACKAGES],
            [AmenityCard::class, Module::AMENITIES],
        ];
    }
}
