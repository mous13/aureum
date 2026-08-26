<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\AmenityCard;
use Citadel\Aureum\Core\Entity\AmenityCardComment;
use Citadel\Aureum\Core\Repository\AmenityCardCommentRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AmenityCardCommentService
{
    public const MAX_LENGTH = 2000;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly Security $security,
        private readonly AmenityCardCommentRepository $commentRepository,
    ) {
    }

    public function add(AmenityCard $card, string $body): ?AmenityCardComment
    {
        $body = trim(mb_substr($body, 0, self::MAX_LENGTH));
        if ($body === '') {
            return null;
        }

        $comment = new AmenityCardComment();
        $comment->setAuthor($this->aureumService->getEmployee());
        $comment->setBody($body);
        $card->addComment($comment);

        $this->commentRepository->save($comment);

        return $comment;
    }

    public function delete(AmenityCardComment $comment): void
    {
        if (!$this->canModify($comment)) {
            throw new AccessDeniedException('You cannot change this comment.');
        }

        $this->commentRepository->remove($comment);
    }

    public function canModify(AmenityCardComment $comment): bool
    {
        if ($this->security->isGranted('aureum.module.amenities.manage')) {
            return true;
        }

        $employee = $this->aureumService->getEmployee();

        return $employee !== null && $comment->getAuthor()->getId() === $employee->getId();
    }
}
