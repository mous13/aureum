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
    private function build(): Menu
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $name): string => "/{$name}");

        $menu = new Menu('ROOT');
        (new AureumMenuBuilder($urlGenerator))->build($menu);

        return $menu;
    }

    /**
     * @return array<string, array{permission: string, location: string}>
     */
    private function leavesByLabel(): array
    {
        $found = [];
        $walk = static function (Menu $menu) use (&$walk, &$found): void {
            foreach ($menu->getEntries() as $entry) {
                if ($entry instanceof MenuItem) {
                    $found[$entry->label] = [
                        'permission' => $entry->getPermission() ?? '',
                        'location' => $entry->location,
                    ];
                    continue;
                }

                $walk($entry);
            }
        };
        $walk($this->build());

        return $found;
    }

    private function submenu(string $label): ?Menu
    {
        foreach ($this->build()->getEntries() as $group) {
            if (!$group instanceof Menu) {
                continue;
            }

            foreach ($group->getEntries() as $child) {
                if ($child instanceof Menu && $child->label === $label) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string>
     */
    private function childLabels(Menu $menu): array
    {
        return array_map(static fn (MenuItem $item): string => $item->label, $menu->getEntries());
    }

    public function testFlatInventoryEntriesArePresentWithTheRightPermissions(): void
    {
        $items = $this->leavesByLabel();

        self::assertSame('aureum.module.inventory.view', $items['Inventory']['permission']);
        self::assertSame('aureum.module.inventory.view', $items['Reorder']['permission']);
        self::assertSame('aureum.module.inventory.count', $items['Record Movement']['permission']);
    }

    public function testManageInventoryIsASubmenuOverItsFourEntities(): void
    {
        $submenu = $this->submenu('Manage Inventory');

        self::assertNotNull($submenu);
        self::assertSame('aureum.module.inventory.manage', $submenu->getPermission());
        self::assertArrayNotHasKey('location', $submenu->options);
        self::assertSame(
            ['Inventories', 'Categories', 'Storage Locations', 'Items'],
            $this->childLabels($submenu),
        );
    }

    public function testEachManageInventoryChildTargetsItsOwnPage(): void
    {
        $items = $this->leavesByLabel();

        self::assertSame('/aureum_inventory_manage_inventories', $items['Inventories']['location']);
        self::assertSame('/aureum_inventory_manage_categories', $items['Categories']['location']);
        self::assertSame('/aureum_inventory_manage_locations', $items['Storage Locations']['location']);
        self::assertSame('/aureum_inventory_manage_items', $items['Items']['location']);
    }

    public function testManageRoomsGroupsItsTwoEntities(): void
    {
        $submenu = $this->submenu('Manage Rooms');

        self::assertNotNull($submenu);
        self::assertSame('aureum.module.rooms.manage', $submenu->getPermission());
        self::assertArrayNotHasKey('location', $submenu->options);
        self::assertSame(['Floors', 'Room Types'], $this->childLabels($submenu));
    }

    public function testEverySubmenuChildCarriesItsOwnPermission(): void
    {
        foreach (['Manage Inventory', 'Manage Rooms'] as $label) {
            $submenu = $this->submenu($label);
            self::assertNotNull($submenu);

            foreach ($submenu->getEntries() as $child) {
                self::assertNotNull(
                    $child->getPermission(),
                    "{$label} child {$child->label} has no permission and would show to everyone",
                );
            }
        }
    }

    public function testExistingEntriesAreUntouched(): void
    {
        $items = $this->leavesByLabel();

        self::assertSame('aureum.module.packages.view', $items['Packages']['permission']);
        self::assertSame('aureum.module.restaurants.view', $items['Restaurants']['permission']);
        self::assertSame('aureum.rbac.manage', $items['Roles']['permission']);
    }
}
