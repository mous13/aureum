<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\FloorFeature;
use Citadel\Aureum\Core\Entity\FloorFeatureComment;
use Citadel\Aureum\Core\Entity\Room;
use Citadel\Aureum\Core\Entity\RoomComment;
use Citadel\Aureum\Core\Repository\FloorFeatureCommentRepository;
use Citadel\Aureum\Core\Repository\RoomCommentRepository;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RoomCommentService
{
    public const MAX_LENGTH = 2000;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly RoomCommentRepository $roomCommentRepository,
        private readonly FloorFeatureCommentRepository $featureCommentRepository,
        private readonly Security $security,
    ) {
    }

    public function getRoomComments(Room $room): array
    {
        return $this->roomCommentRepository->findByRoom($room);
    }

    public function getFeatureComments(FloorFeature $feature): array
    {
        return $this->featureCommentRepository->findByFeature($feature);
    }

    public function addRoomComment(Room $room, string $body): RoomComment
    {
        $comment = new RoomComment();
        $comment->setRoom($room);
        $comment->setAuthor($this->requireEmployee());
        $comment->setBody($body);

        $this->roomCommentRepository->save($comment);

        return $comment;
    }

    public function addFeatureComment(FloorFeature $feature, string $body): FloorFeatureComment
    {
        $comment = new FloorFeatureComment();
        $comment->setFeature($feature);
        $comment->setAuthor($this->requireEmployee());
        $comment->setBody($body);

        $this->featureCommentRepository->save($comment);

        return $comment;
    }

    public function update(RoomComment|FloorFeatureComment $comment, string $body): void
    {
        $this->denyUnlessCanModify($comment);

        $comment->setBody($body);
        $comment->setEditedAt(new DateTime());

        $this->repositoryFor($comment)->save($comment);
    }

    public function delete(RoomComment|FloorFeatureComment $comment): void
    {
        $this->denyUnlessCanModify($comment);

        $this->repositoryFor($comment)->remove($comment);
    }

    public function canModify(RoomComment|FloorFeatureComment $comment): bool
    {
        if ($this->security->isGranted('aureum.module.rooms.manage')) {
            return true;
        }

        $employee = $this->aureumService->getEmployee();

        return $employee !== null && $comment->getAuthor()->getId() === $employee->getId();
    }

    public function countsForRooms(array $rooms): array
    {
        return $this->roomCommentRepository->countBySubjectIds(
            array_map(static fn (Room $room) => $room->getId(), $rooms),
        );
    }

    public function countsForFeatures(array $features): array
    {
        return $this->featureCommentRepository->countBySubjectIds(
            array_map(static fn (FloorFeature $feature) => $feature->getId(), $features),
        );
    }

    public function countsForHotel(int $hotelId): array
    {
        return $this->roomCommentRepository->countByHotel($hotelId);
    }

    private function denyUnlessCanModify(RoomComment|FloorFeatureComment $comment): void
    {
        if (!$this->canModify($comment)) {
            throw new AccessDeniedException('You cannot change this comment.');
        }
    }

    private function repositoryFor(
        RoomComment|FloorFeatureComment $comment,
    ): RoomCommentRepository|FloorFeatureCommentRepository {
        return $comment instanceof RoomComment
            ? $this->roomCommentRepository
            : $this->featureCommentRepository;
    }

    private function requireEmployee(): Employee
    {
        $employee = $this->aureumService->getEmployee();
        if ($employee === null) {
            throw new AccessDeniedException('Only employees can comment.');
        }

        return $employee;
    }
}
