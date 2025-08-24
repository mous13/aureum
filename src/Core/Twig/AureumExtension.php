<?php

namespace Citadel\Aureum\Core\Twig;

use Citadel\Aureum\Core\MenuBuilder\AureumMenuManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AureumExtension extends AbstractExtension
{
    public function __construct(
        private readonly AureumMenuManager $aureumMenuManager
    ){
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('aureum_menu', $this->aureumMenuManager->getMenu(...)),
        ];
    }

}