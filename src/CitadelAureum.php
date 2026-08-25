<?php

declare(strict_types=1);

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
                'view',
                'announcements' => [
                    'view',
                    'manage',
                ],
                'hotels' => [
                    'view',
                    'manage',
                    'delete',
                ],
                'employees' => [
                    'manage',
                ],
            ],
            'core' => [
                'concierge' => [
                    'view',
                    'manage',
                ],
            ],
        ];
    }
}
