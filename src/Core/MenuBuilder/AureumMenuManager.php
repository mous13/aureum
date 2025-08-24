<?php

namespace Citadel\Aureum\Core\MenuBuilder;

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
}