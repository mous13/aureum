<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\CommentEntityTrait;
use Citadel\Aureum\Core\Repository\AmenityCardCommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: AmenityCardCommentRepository::class)]
#[ORM\Table(name: 'aureum_amenity_card_comments')]
#[ORM\Index(name: 'idx_amenity_card_comment', columns: ['card_id', 'created_at'])]
class AmenityCardComment implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;
    use CommentEntityTrait;

    #[ORM\ManyToOne(targetEntity: AmenityCard::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AmenityCard $card;

    public function getHotel(): ?Hotel
    {
        return $this->card->getHotel();
    }

    public function getCard(): AmenityCard
    {
        return $this->card;
    }

    public function setCard(AmenityCard $card): void
    {
        $this->card = $card;
    }

    public function getSubjectType(): string
    {
        return 'amenity_card';
    }

    public function getSubjectId(): int
    {
        return $this->card->getId();
    }
}
