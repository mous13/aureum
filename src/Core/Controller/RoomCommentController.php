<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\FloorFeature;
use Citadel\Aureum\Core\Entity\FloorFeatureComment;
use Citadel\Aureum\Core\Entity\Room;
use Citadel\Aureum\Core\Entity\RoomComment;
use Citadel\Aureum\Core\Repository\FloorFeatureCommentRepository;
use Citadel\Aureum\Core\Repository\FloorFeatureRepository;
use Citadel\Aureum\Core\Repository\RoomCommentRepository;
use Citadel\Aureum\Core\Repository\RoomRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\RoomCommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rooms/directory', name: 'room_comments_')]
#[IsGranted('aureum.module.rooms.view')]
class RoomCommentController extends AbstractController
{
    private const TYPE_REQUIREMENT = 'room|feature';
    public const CSRF_TOKEN_ID = 'aureum_room_comments';

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly RoomCommentService $commentService,
        private readonly RoomRepository $roomRepository,
        private readonly FloorFeatureRepository $floorFeatureRepository,
        private readonly RoomCommentRepository $roomCommentRepository,
        private readonly FloorFeatureCommentRepository $featureCommentRepository,
    ) {
    }

    #[Route(
        '/{type}/{id}/comments',
        name: 'list',
        requirements: ['type' => self::TYPE_REQUIREMENT, 'id' => '\d+'],
        methods: ['GET'],
    )]
    public function list(string $type, int $id): JsonResponse
    {
        $subject = $this->findSubject($type, $id);

        return new JsonResponse(['comments' => $this->serializeAll($this->commentsFor($subject))]);
    }

    #[Route(
        '/{type}/{id}/comments',
        name: 'add',
        requirements: ['type' => self::TYPE_REQUIREMENT, 'id' => '\d+'],
        methods: ['POST'],
    )]
    public function add(Request $request, string $type, int $id): JsonResponse
    {
        $this->denyUnlessValidCsrf($request);

        $subject = $this->findSubject($type, $id);

        $body = $this->readBody($request);
        if ($body === null) {
            return $this->invalidBody();
        }

        $subject instanceof Room
            ? $this->commentService->addRoomComment($subject, $body)
            : $this->commentService->addFeatureComment($subject, $body);

        return new JsonResponse(['comments' => $this->serializeAll($this->commentsFor($subject))]);
    }

    #[Route(
        '/{type}/comments/{id}',
        name: 'update',
        requirements: ['type' => self::TYPE_REQUIREMENT, 'id' => '\d+'],
        methods: ['PATCH'],
    )]
    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $this->denyUnlessValidCsrf($request);

        $comment = $this->findComment($type, $id);

        $body = $this->readBody($request);
        if ($body === null) {
            return $this->invalidBody();
        }

        $this->commentService->update($comment, $body);

        return new JsonResponse(['comments' => $this->serializeAll($this->commentsForComment($comment))]);
    }

    #[Route(
        '/{type}/comments/{id}',
        name: 'delete',
        requirements: ['type' => self::TYPE_REQUIREMENT, 'id' => '\d+'],
        methods: ['DELETE'],
    )]
    public function delete(Request $request, string $type, int $id): JsonResponse
    {
        $this->denyUnlessValidCsrf($request);

        $comment = $this->findComment($type, $id);
        $subject = $comment instanceof RoomComment ? $comment->getRoom() : $comment->getFeature();

        $this->commentService->delete($comment);

        return new JsonResponse(['comments' => $this->serializeAll($this->commentsFor($subject))]);
    }

    private function findSubject(string $type, int $id): Room|FloorFeature
    {
        $subject = $type === 'room'
            ? $this->roomRepository->find($id)
            : $this->floorFeatureRepository->find($id);

        if ($subject === null) {
            throw $this->createNotFoundException();
        }

        $this->denyUnlessSameHotel($subject);

        return $subject;
    }

    private function findComment(string $type, int $id): RoomComment|FloorFeatureComment
    {
        $comment = $type === 'room'
            ? $this->roomCommentRepository->find($id)
            : $this->featureCommentRepository->find($id);

        if ($comment === null) {
            throw $this->createNotFoundException();
        }

        $this->denyUnlessSameHotel($comment instanceof RoomComment ? $comment->getRoom() : $comment->getFeature());

        return $comment;
    }

    private function commentsFor(Room|FloorFeature $subject): array
    {
        return $subject instanceof Room
            ? $this->commentService->getRoomComments($subject)
            : $this->commentService->getFeatureComments($subject);
    }

    private function commentsForComment(RoomComment|FloorFeatureComment $comment): array
    {
        return $this->commentsFor($comment instanceof RoomComment ? $comment->getRoom() : $comment->getFeature());
    }

    private function serializeAll(array $comments): array
    {
        $employee = $this->aureumService->getEmployee();

        return array_map(fn (RoomComment|FloorFeatureComment $comment) => [
            'id' => $comment->getId(),
            'url' => $this->generateUrl('aureum_room_comments_update', [
                'type' => $comment->getSubjectType(),
                'id' => $comment->getId(),
            ]),
            'body' => $comment->getBody(),
            'author' => $comment->getAuthor()->getName(),
            'mine' => $employee !== null && $comment->getAuthor()->getId() === $employee->getId(),
            'canModify' => $this->commentService->canModify($comment),
            'createdAt' => $comment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'editedAt' => $comment->getEditedAt()?->format(\DateTimeInterface::ATOM),
        ], $comments);
    }

    private function readBody(Request $request): ?string
    {
        $body = trim((string)($request->toArray()['body'] ?? ''));

        return $body === '' || mb_strlen($body) > RoomCommentService::MAX_LENGTH ? null : $body;
    }

    private function invalidBody(): JsonResponse
    {
        return new JsonResponse(
            ['error' => sprintf('Write a comment of up to %d characters.', RoomCommentService::MAX_LENGTH)],
            Response::HTTP_BAD_REQUEST,
        );
    }

    private function denyUnlessValidCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string)$request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    private function denyUnlessSameHotel(Room|FloorFeature $subject): void
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null || $subject->getFloor()->getHotel()->getId() !== $hotel->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
