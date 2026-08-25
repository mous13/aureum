<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\AmenityCard;
use PHPUnit\Framework\TestCase;

class AmenityCardTest extends TestCase
{
    public function testItemsTextParsesOneItemPerLine(): void
    {
        $card = new AmenityCard();
        $card->setItemsText("2x Beer\n1x Card\n\n  \n1x Fruit Basket  ");

        self::assertSame([
            ['label' => '2x Beer', 'done' => false],
            ['label' => '1x Card', 'done' => false],
            ['label' => '1x Fruit Basket', 'done' => false],
        ], $card->getItems());
    }

    public function testItemsTextJoinsLabels(): void
    {
        $card = new AmenityCard();
        $card->setItems([
            ['label' => '2x Beer', 'done' => true],
            ['label' => '1x Card', 'done' => false],
        ]);

        self::assertSame("2x Beer\n1x Card", $card->getItemsText());
    }

    public function testEditingItemsKeepsTheTicksOfSurvivingLabels(): void
    {
        $card = new AmenityCard();
        $card->setItems([
            ['label' => '2x Beer', 'done' => true],
            ['label' => '1x Card', 'done' => false],
        ]);

        $card->setItemsText("1x Card\n2x Beer\n1x Sweets");

        self::assertSame([
            ['label' => '1x Card', 'done' => false],
            ['label' => '2x Beer', 'done' => true],
            ['label' => '1x Sweets', 'done' => false],
        ], $card->getItems());
    }

    public function testToggleItemFlipsOnlyThatItem(): void
    {
        $card = new AmenityCard();
        $card->setItemsText("2x Beer\n1x Card");

        $card->toggleItem(1);

        self::assertFalse($card->getItems()[0]['done']);
        self::assertTrue($card->getItems()[1]['done']);

        $card->toggleItem(1);

        self::assertFalse($card->getItems()[1]['done']);
    }

    public function testTogglingAMissingIndexDoesNothing(): void
    {
        $card = new AmenityCard();
        $card->setItemsText('2x Beer');

        $card->toggleItem(5);
        $card->toggleItem(-1);

        self::assertSame([['label' => '2x Beer', 'done' => false]], $card->getItems());
    }

    public function testDoneCountCountsTickedItems(): void
    {
        $card = new AmenityCard();
        $card->setItemsText("2x Beer\n1x Card\n1x Sweets");
        $card->toggleItem(0);
        $card->toggleItem(2);

        self::assertSame(2, $card->getDoneCount());
    }

    public function testItemsAreNormalisedFromLooseStorage(): void
    {
        $card = new AmenityCard();
        $card->setItems([
            ['label' => '2x Beer', 'done' => 1],
            ['label' => 'sweets'],
        ]);

        self::assertSame([
            ['label' => '2x Beer', 'done' => true],
            ['label' => 'sweets', 'done' => false],
        ], $card->getItems());
    }
}
