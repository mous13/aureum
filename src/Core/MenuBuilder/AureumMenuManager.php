<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\MenuBuilder;

use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuManager;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Bundle\SecurityBundle\Security;
use Forumify\Core\MenuBuilder\MenuBuilderInterface;

class AureumMenuManager extends MenuManager
{
    /**
     * @param iterable<MenuBuilderInterface> $menuBuilders
     */
    public function __construct(
        #[AutowireIterator('forumify.menu_builder.aureum')]
        iterable $menuBuilders,
        Security $security,
    ) {
        parent::__construct($menuBuilders, $security);
    }

    public function getMenu(): Menu
    {
        $menu = parent::getMenu();
        $this->pruneEmptyMenus($menu);

        return $menu;
    }

    private function pruneEmptyMenus(Menu $menu): void
    {
        foreach ($menu->getEntries() as $position => $item) {
            if (!$item instanceof Menu) {
                continue;
            }

            $this->pruneEmptyMenus($item);
            if ($item->getEntries() === []) {
                $menu->removeItemAt($position);
            }
        }
    }
}
