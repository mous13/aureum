<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Announcement;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Repository\AnnouncementRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Aureum\Announcements', '@CitadelAureum/core/components/announcements.html.twig')]
class Announcements
{
    public string $module;

    public function __construct(
        private readonly AnnouncementRepository $announcementRepository,
    ) {
    }

    /**
     * @return array<Announcement>
     */
    public function getAnnouncements(): array
    {
        $module = Module::tryFrom($this->module);

        return $module === null ? [] : $this->announcementRepository->findByModule($module);
    }
}
