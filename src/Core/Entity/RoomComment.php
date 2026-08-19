<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\CommentEntityTrait;
use Citadel\Aureum\Core\Repository\RoomCommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: RoomCommentRepository::class)]
#[ORM\Table(name: 'aureum_room_comments')]
#[ORM\Index(columns: ['room_id', 'created_at'])]
class RoomComment implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;
    use CommentEntityTrait;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Room $room;

    public function getHotel(): ?Hotel
    {
        return $this->room->getHotel();
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): void
    {
        $this->room = $room;
    }

    public function getSubjectType(): string
    {
        return 'room';
    }

    public function getSubjectId(): int
    {
        return $this->room->getId();
    }
}
