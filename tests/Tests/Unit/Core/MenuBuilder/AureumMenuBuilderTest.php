<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\MenuBuilder;

use Citadel\Aureum\Core\MenuBuilder\AureumMenuBuilder;
use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AureumMenuBuilderTest extends TestCase
{
    /**
     * @return array<string, array{label: string, permission: string}>
     */
    private function itemsByLabel(): array
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $name): string => "/{$name}");

        $menu = new Menu('ROOT');
        (new AureumMenuBuilder($urlGenerator))->build($menu);

        $found = [];
        foreach ($menu->getEntries() as $entry) {
            if (!$entry instanceof Menu) {
                continue;
            }

            foreach ($entry->getEntries() as $child) {
                if ($child instanceof MenuItem) {
                    $found[$child->label] = [
                        'label' => $child->label,
                        'permission' => $child->getPermission() ?? '',
                    ];
                }
            }
        }

        return $found;
    }

    public function testInventoryEntriesArePresent(): void
    {
        $items = $this->itemsByLabel();

        self::assertArrayHasKey('Inventory', $items);
        self::assertArrayHasKey('Reorder', $items);
        self::assertArrayHasKey('Record Movement', $items);
        self::assertArrayHasKey('Manage Inventory', $items);
    }

    public function testInventoryEntriesCarryTheRightPermissions(): void
    {
        $items = $this->itemsByLabel();

        self::assertSame('aureum.module.inventory.view', $items['Inventory']['permission']);
        self::assertSame('aureum.module.inventory.view', $items['Reorder']['permission']);
        self::assertSame('aureum.module.inventory.count', $items['Record Movement']['permission']);
        self::assertSame('aureum.module.inventory.manage', $items['Manage Inventory']['permission']);
    }

    public function testExistingEntriesAreUntouched(): void
    {
        $items = $this->itemsByLabel();

        self::assertSame('aureum.module.packages.view', $items['Packages']['permission']);
        self::assertSame('aureum.module.rooms.manage', $items['Manage Floors']['permission']);
    }
}
