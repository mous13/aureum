<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\AnonymisableInterface;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Entity\Package;
use Citadel\Aureum\Core\Entity\Transfer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnonymisationTest extends TestCase
{
    public function testTransferLosesEveryGuestIdentifier(): void
    {
        $transfer = new Transfer();
        $transfer->setGuest('Sam Whitfield');
        $transfer->setNumber('07700 900001');
        $transfer->setEmail('sam@example.test');
        $transfer->setPickup('12 Old Street, London');
        $transfer->setDropoff('Heathrow T5');
        $transfer->setDriver('Dave');
        $transfer->setNotes('guest asked for a quiet driver');
        $transfer->setCost('45.00');

        $transfer->anonymise();

        self::assertNull($transfer->getGuest());
        self::assertNull($transfer->getNumber());
        self::assertNull($transfer->getEmail());
        self::assertNull($transfer->getPickup());
        self::assertNull($transfer->getDropoff());
        self::assertNull($transfer->getDriver());
        self::assertNull($transfer->getNotes());
        self::assertTrue($transfer->isAnonymised());

        // Non-identifying operational data is the point of keeping the row.
        self::assertSame('45.00', $transfer->getCost());
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

        // The fine reference is not personal data and is what makes the
        // remaining row useful for counts and dispute history.
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
            'transfer' => [static fn (): Transfer => new Transfer()],
            'fine' => [static fn (): Fine => new Fine()],
            'lost property' => [static fn (): LostProperty => new LostProperty()],
            'package' => [static fn (): Package => new Package()],
        ];
    }

    public static function moduleProvider(): array
    {
        return [
            [Transfer::class, Module::TRANSFERS],
            [Fine::class, Module::FINES],
            [LostProperty::class, Module::LOST_PROPERTY],
            [Package::class, Module::PACKAGES],
        ];
    }
}
