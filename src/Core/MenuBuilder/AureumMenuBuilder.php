<?php

namespace Citadel\Aureum\Core\MenuBuilder;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuItem;
class AureumMenuBuilder implements AureumMenuBuilderInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ){
    }

    public function build(Menu $menu): void
    {
        $url = $this->urlGenerator->generate(...);

        $menu
            ->addItem(new MenuItem('Dashboard', $url('aureum_dashboard'), [
                'icon' => 'ph ph-gauge',
            ]))
            ->addItem(new MenuItem('Packages', $url('aureum_dashboard'), [
                'icon' => 'ph ph-package',
            ]))
            ->addItem(new MenuItem('Transfers', $url('aureum_dashboard'), [
                'icon' => 'ph ph-car',
            ]))
            ->addItem(new MenuItem('Fines', $url('aureum_dashboard'), [
                'icon' => 'ph ph-article',
            ]))
        ;
    }
}