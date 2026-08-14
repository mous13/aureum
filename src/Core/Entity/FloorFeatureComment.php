<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\CommentEntityTrait;
use Citadel\Aureum\Core\Repository\FloorFeatureCommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: FloorFeatureCommentRepository::class)]
#[ORM\Table(name: 'aureum_floor_feature_comments')]
#[ORM\Index(columns: ['feature_id', 'created_at'])]
class FloorFeatureComment
{
    use IdentifiableEntityTrait;
    use CommentEntityTrait;

    #[ORM\ManyToOne(targetEntity: FloorFeature::class)]
    #[ORM\JoinColumn(name: 'feature_id', nullable: false, onDelete: 'CASCADE')]
    private FloorFeature $feature;

    public function getFeature(): FloorFeature
    {
        return $this->feature;
    }

    public function setFeature(FloorFeature $feature): void
    {
        $this->feature = $feature;
    }

    public function getSubjectType(): string
    {
        return 'feature';
    }

    public function getSubjectId(): int
    {
        return $this->feature->getId();
    }
}
