<?php

namespace Citadel\Aureum;

use Forumify\Plugin\AbstractForumifyPlugin;
use Forumify\Plugin\PluginMetadata;

class CitadelAureum extends AbstractForumifyPlugin
{
    public function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata(
            'Aureum',
            'Citadel Software Solutions',
        );
    }

    public function getPermissions(): array
    {
        return [
            'admin' => [
                'view'
            ],
            'core' => [
                'concierge' => [
                    'view',
                    'manage'
                ]
            ]
        ];
    }

}