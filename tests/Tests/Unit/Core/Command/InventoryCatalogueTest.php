<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Command;

use Citadel\Aureum\Core\Command\SeedInventoryCommand;
use PHPUnit\Framework\TestCase;

class InventoryCatalogueTest extends TestCase
{
    public function testCategoriesMatchTheDepartmentStockTake(): void
    {
        self::assertSame(
            ['Essentials', 'Amenities', 'Stationary', 'Concierge'],
            array_keys(SeedInventoryCommand::CATALOGUE),
        );
    }

    public function testEveryItemDeclaresANameAndUnit(): void
    {
        foreach (SeedInventoryCommand::CATALOGUE as $category => $items) {
            self::assertNotEmpty($items, "{$category} has no items");

            foreach ($items as $item) {
                self::assertArrayHasKey('name', $item);
                self::assertArrayHasKey('unit', $item);
                self::assertNotSame('', $item['name']);
                self::assertNotSame('', $item['unit']);
            }
        }
    }

    public function testItemsWithAPackSizeAlsoDeclareAPackLabel(): void
    {
        foreach (SeedInventoryCommand::CATALOGUE as $items) {
            foreach ($items as $item) {
                if ($item['packSize'] === null) {
                    continue;
                }

                self::assertNotNull($item['packLabel'], "{$item['name']} has a pack size but no label");
                self::assertGreaterThan(1, $item['packSize']);
            }
        }
    }

    public function testKeyCardsComeInBoxes(): void
    {
        $keyCards = null;
        foreach (SeedInventoryCommand::CATALOGUE['Essentials'] as $item) {
            if ($item['name'] === 'Key Cards') {
                $keyCards = $item;
            }
        }

        self::assertNotNull($keyCards);
        self::assertSame('card', $keyCards['unit']);
        self::assertSame('box', $keyCards['packLabel']);
    }
}
