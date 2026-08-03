<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\MenuBuilder;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuItem;

class AureumMenuBuilder implements AureumMenuBuilderInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function build(Menu $menu): void
    {
        $url = $this->urlGenerator->generate(...);


        $general = new Menu('GENERAL', ['icon' => ''], [
            new MenuItem('Packages', $url('aureum_packages'), [
                'icon' => 'ph ph-package',
            ]),
            new MenuItem('Transfers', $url('aureum_transfers'), [
                'icon' => 'ph ph-car',
            ]),
            new MenuItem('Lost Property', $url('aureum_lost_property'), [
                'icon' => 'ph ph-t-shirt',
            ]),
            new MenuItem('Fines', $url('aureum_fines'), [
                'icon' => 'ph ph-article',
            ]),
        ]);

        $menu->addItem($general);

        $directory = new Menu('DIRECTORY', ['icon' => ''], [
            new MenuItem('Restaurants', $url('aureum_restaurants_list'), [
                'icon' => 'ph ph-bowl-steam',
            ]),
            new MenuItem('Rooms Directory', $url('aureum_rooms_directory'), [
                'icon' => 'ph ph-door',
                'permission' => 'aureum.core.rooms.view',
            ]),
            new MenuItem('Events', $url('aureum_events_calendar'), [
                'icon' => 'ph ph-calendar',
            ]),
        ]);

        $menu->addItem($directory);

        $manager = new Menu('MANAGER', ['icon' => ''], [
            new MenuItem('Manage Floors', $url('aureum_floors_list'), [
                'icon' => 'ph ph-blueprint',
                'permission' => 'aureum.core.rooms.manage',
            ]),
            new MenuItem('Room Types', $url('aureum_room_types_list'), [
                'icon' => 'ph ph-bed',
                'permission' => 'aureum.core.rooms.manage',
            ]),
        ]);

        $menu->addItem($manager);

        $settings = new Menu('SETTINGS', ['icon' => ''], [
            new MenuItem('Settings', $url('forumify_core_settings'), [
                'icon' => 'ph ph-gear',
            ]),
        ]);

        $menu->addItem($settings);
    }
}
