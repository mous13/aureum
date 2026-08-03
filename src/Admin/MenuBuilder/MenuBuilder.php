<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\MenuBuilder;

use Forumify\Admin\MenuBuilder\AdminMenuBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuItem;

class MenuBuilder implements AdminMenuBuilderInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function build(Menu $menu): void
    {
        $url = $this->urlGenerator->generate(...);

        $aureumMenu = new Menu(
            'Aureum',
            ['icon' => 'ph ph-key', 'permission' => 'aureum.admin.view'],
            [
                new MenuItem('Hotels', $url('aureum_admin_hotels_list'), ['icon' => 'ph ph-building', 'permission' => 'aureum.admin.view']),
                new MenuItem('Announcements', $url('aureum_admin_announcements_list'), ['icon' => 'ph ph-megaphone', 'permission' => 'aureum.admin.announcements.view']),
            ]
        );

        $menu->addItem($aureumMenu);
    }
}
