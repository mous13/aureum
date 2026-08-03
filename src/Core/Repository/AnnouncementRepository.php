<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Announcement;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Announcement>
 */
class AnnouncementRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Announcement::class;
    }

    /**
     * @return array<Announcement>
     */
    public function findByModule(Module $module): array
    {
        return $this->findBy(['module' => $module], ['createdAt' => 'DESC']);
    }
}
