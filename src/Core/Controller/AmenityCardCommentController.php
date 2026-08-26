<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\AmenityCard;
use Citadel\Aureum\Core\Entity\AmenityCardComment;
use Citadel\Aureum\Core\Repository\AmenityBoardRepository;
use Citadel\Aureum\Core\Repository\AmenityCardCommentRepository;
use Citadel\Aureum\Core\Repository\AmenityCardRepository;
use Citadel\Aureum\Core\Service\AmenityCardCommentService;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/amenities', name: 'amenity_comments_')]
#[IsGranted('aureum.module.amenities.view')]
class AmenityCardCommentController extends AbstractController
{
    public const CSRF_TOKEN_ID = 'aureum_amenity_comments';

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly AmenityCardCommentService $commentService,
        private readonly AmenityCardRepository $cardRepository,
        private readonly AmenityCardCommentRepository $commentRepository,
        private readonly AmenityBoardRepository $boardRepository,
    ) {
    }

    #[Route('/card/{id}/comments', name: 'list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function list(int $id): JsonResponse
    {
        $card = $this->findCard($id);

        return new JsonResponse(['comments' => $this->serializeAll($this->commentService->getComments($card))]);
    }

    #[Route('/card/{id}/comments', name: 'add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(Request $request, int $id): JsonResponse
    {
        $this->denyUnlessValidCsrf($request);

        $card = $this->findCard($id);
        $this->denyUnlessActiveBoard($card);

        $body = $this->readBody($request);
        if ($body === null) {
            return $this->invalidBody();
        }

        $this->commentService->add($card, $body);

        return new JsonResponse(['comments' => $this->serializeAll($this->commentService->getComments($card))]);
    }

    #[Route('/card/comments/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $this->denyUnlessValidCsrf($request);

        $comment = $this->findComment($id);
        $this->denyUnlessActiveBoard($comment->getCard());

        $body = $this->readBody($request);
        if ($body === null) {
            return $this->invalidBody();
        }

        $this->commentService->update($comment, $body);

        return new JsonResponse(['comments' => $this->serializeAll($this->commentService->getComments($comment->getCard()))]);
    }

    #[Route('/card/comments/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        $this->denyUnlessValidCsrf($request);

        $comment = $this->findComment($id);
        $this->denyUnlessActiveBoard($comment->getCard());
        $card = $comment->getCard();

        $this->commentService->delete($comment);

        return new JsonResponse(['comments' => $this->serializeAll($this->commentService->getComments($card))]);
    }

    private function findCard(int $id): AmenityCard
    {
        $card = $this->cardRepository->find($id);
        if ($card === null || $card->getHotel()->getId() !== $this->aureumService->getHotel()?->getId()) {
            throw $this->createNotFoundException();
        }

        return $card;
    }

    private function findComment(int $id): AmenityCardComment
    {
        $comment = $this->commentRepository->find($id);
        if ($comment === null) {
            throw $this->createNotFoundException();
        }

        $this->findCard($comment->getCard()->getId());

        return $comment;
    }

    private function denyUnlessActiveBoard(AmenityCard $card): void
    {
        $latest = $this->boardRepository->findLatest($card->getHotel());
        if ($card->getBoard()->getId() !== $latest?->getId()) {
            throw $this->createAccessDeniedException('This board is archived.');
        }
    }

    /** @param array<AmenityCardComment> $comments */
    private function serializeAll(array $comments): array
    {
        $employee = $this->aureumService->getEmployee();

        return array_map(fn (AmenityCardComment $comment) => [
            'id' => $comment->getId(),
            'url' => $this->generateUrl('aureum_amenity_comments_update', ['id' => $comment->getId()]),
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

        return $body === '' || mb_strlen($body) > AmenityCardCommentService::MAX_LENGTH ? null : $body;
    }

    private function invalidBody(): JsonResponse
    {
        return new JsonResponse(
            ['error' => sprintf('Write a comment of up to %d characters.', AmenityCardCommentService::MAX_LENGTH)],
            Response::HTTP_BAD_REQUEST,
        );
    }

    private function denyUnlessValidCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string)$request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
