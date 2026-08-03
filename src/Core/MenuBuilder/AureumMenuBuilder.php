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
                'permission' => 'aureum.module.packages.view',
            ]),
            new MenuItem('Transfers', $url('aureum_transfers'), [
                'icon' => 'ph ph-car',
                'permission' => 'aureum.module.transfers.view',
            ]),
            new MenuItem('Lost Property', $url('aureum_lost_property'), [
                'icon' => 'ph ph-t-shirt',
                'permission' => 'aureum.module.lost_property.view',
            ]),
            new MenuItem('Fines', $url('aureum_fines'), [
                'icon' => 'ph ph-article',
                'permission' => 'aureum.module.fines.view',
            ]),
        ]);

        $menu->addItem($general);

        $directory = new Menu('DIRECTORY', ['icon' => ''], [
            new MenuItem('Restaurants', $url('aureum_restaurants_list'), [
                'icon' => 'ph ph-bowl-steam',
                'permission' => 'aureum.module.restaurants.view',
            ]),
            new MenuItem('Rooms Directory', $url('aureum_rooms_directory'), [
                'icon' => 'ph ph-door',
                'permission' => 'aureum.module.rooms.view',
            ]),
            new MenuItem('Events', $url('aureum_events_calendar'), [
                'icon' => 'ph ph-calendar',
                'permission' => 'aureum.module.events.view',
            ]),
        ]);

        $menu->addItem($directory);

        $manager = new Menu('MANAGER', ['icon' => ''], [
            new MenuItem('Manage Floors', $url('aureum_floors_list'), [
                'icon' => 'ph ph-blueprint',
                'permission' => 'aureum.module.rooms.manage',
            ]),
            new MenuItem('Room Types', $url('aureum_room_types_list'), [
                'icon' => 'ph ph-bed',
                'permission' => 'aureum.module.rooms.manage',
            ]),
            new MenuItem('Roles', $url('aureum_roles_list'), [
                'icon' => 'ph ph-users-three',
                'permission' => 'aureum.rbac.manage',
            ]),
            new MenuItem('Modules', $url('aureum_modules_edit'), [
                'icon' => 'ph ph-squares-four',
                'permission' => 'aureum.rbac.manage',
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
