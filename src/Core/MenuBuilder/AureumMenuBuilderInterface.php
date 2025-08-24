<?php

namespace Citadel\Aureum\Core\MenuBuilder;

use Forumify\Core\MenuBuilder\MenuBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('forumify.menu_builder.aureum')]
interface AureumMenuBuilderInterface extends MenuBuilderInterface
{
}