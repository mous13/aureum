<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\FloorFeature;
use Citadel\Aureum\Core\Entity\FloorFeatureComment;

class FloorFeatureCommentRepository extends AbstractCommentRepository
{
    public static function getEntityClass(): string
    {
        return FloorFeatureComment::class;
    }

    protected function getSubjectReference(): string
    {
        return 'feature';
    }

    public function findByFeature(FloorFeature $feature): array
    {
        return $this->findBySubject($feature);
    }
}
