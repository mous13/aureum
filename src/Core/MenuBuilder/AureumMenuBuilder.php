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


        $general = new Menu('GENERAL', ['icon' => 'ph ph-squares-four'], [
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

        $directory = new Menu('DIRECTORY', ['icon' => 'ph-folder-open'], [
            new MenuItem('Restaurants', $url('aureum_restaurants_list'), [
                'icon' => 'ph ph-bowl-steam',
            ]),
        ]);

        $menu->addItem($directory);

        $settings = new Menu('SETTINGS', ['icon' => 'ph ph-gear'], [
            new MenuItem('Settings', $url('forumify_core_settings'), [
                'icon' => 'ph ph-gear',
            ]),
        ]);

        $menu->addItem($settings);
    }
}
