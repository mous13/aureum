<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\AmenityBoard;
use Citadel\Aureum\Core\Entity\AmenityCard;
use Citadel\Aureum\Core\Entity\Enum\AmenityCardStatus;
use Citadel\Aureum\Core\Form\AmenityCardType;
use Citadel\Aureum\Core\Repository\AmenityBoardRepository;
use Citadel\Aureum\Core\Repository\AmenityCardRepository;
use Citadel\Aureum\Core\Service\AmenityBoardService;
use Citadel\Aureum\Core\Service\AureumService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Aureum\\AmenityBoard', '@CitadelAureum/core/components/amenity_board.html.twig')]
#[IsGranted('aureum.module.amenities.view')]
class AmenityBoardComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public int $boardId;

    #[LiveProp]
    public ?string $createStatus = null;

    #[LiveProp]
    public ?int $editCardId = null;

    private ?AmenityBoard $board = null;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly AmenityBoardService $boardService,
        private readonly AmenityBoardRepository $boardRepository,
        private readonly AmenityCardRepository $cardRepository,
    ) {
    }

    #[LiveAction]
    public function moveCard(#[LiveArg] int $cardId, #[LiveArg] string $status, #[LiveArg] int $position): void
    {
        $this->denyUnlessActive();
        $target = AmenityCardStatus::tryFrom($status);
        $card = $this->findCard($cardId);
        if ($card === null || $target === null) {
            return;
        }

        $this->boardService->moveCard($card, $target, $position);
    }

    #[LiveAction]
    public function advanceCard(#[LiveArg] int $cardId): void
    {
        $this->denyUnlessActive();
        $card = $this->findCard($cardId);
        if ($card === null) {
            return;
        }

        $this->boardService->advanceCard($card);
    }

    #[LiveAction]
    public function toggleItem(#[LiveArg] int $cardId, #[LiveArg] int $index): void
    {
        $this->denyUnlessActive();
        $card = $this->findCard($cardId);
        if ($card === null) {
            return;
        }

        $card->toggleItem($index);
        $card->setUpdatedAt(new DateTime());
        $this->cardRepository->save($card);
    }

    #[LiveAction]
    public function openCreate(#[LiveArg] string $status): void
    {
        $this->denyUnlessCanManage();
        $this->editCardId = null;
        $this->createStatus = $status;
        $this->resetForm();
    }

    #[LiveAction]
    public function openEdit(#[LiveArg] int $cardId): void
    {
        $this->denyUnlessCanManage();
        $this->createStatus = null;
        $this->editCardId = $cardId;
        $this->resetForm();
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->createStatus = null;
        $this->editCardId = null;
        $this->isValidated = false;
        $this->validatedFields = [];
        $this->resetForm();
    }

    #[LiveAction]
    public function save(): void
    {
        $this->denyUnlessCanManage();
        $this->submitForm();

        /** @var AmenityCard $card */
        $card = $this->getForm()->getData();

        if ($this->editCardId === null) {
            $board = $this->getBoard();
            $card->setPosition($this->nextPosition($board, $card->getStatus()));
            $board->addCard($card);
            $card->setHotel($board->getHotel());
        }

        $card->setUpdatedAt(new DateTime());
        $this->cardRepository->save($card);
        $this->closeModal();
    }

    #[LiveAction]
    public function delete(): void
    {
        $this->denyUnlessCanManage();
        $card = $this->findCard($this->editCardId ?? 0);
        if ($card !== null) {
            $this->cardRepository->remove($card);
        }

        $this->closeModal();
    }

    public function getBoard(): AmenityBoard
    {
        if ($this->board !== null) {
            return $this->board;
        }

        $hotel = $this->aureumService->getHotel();
        $board = $this->boardRepository->findOneBy(['id' => $this->boardId, 'hotel' => $hotel]);
        if ($board === null) {
            throw new NotFoundHttpException('Amenity board not found.');
        }

        return $this->board = $board;
    }

    public function isReadOnly(): bool
    {
        $latest = $this->boardRepository->findLatest($this->aureumService->getHotel());

        return $this->getBoard()->getId() !== $latest?->getId();
    }

    public function canManage(): bool
    {
        return !$this->isReadOnly() && $this->isGranted('aureum.module.amenities.manage');
    }

    /** @return array<array{status: AmenityCardStatus, cards: array<AmenityCard>}> */
    public function getColumns(): array
    {
        $columns = [];
        foreach (AmenityCardStatus::cases() as $status) {
            $columns[$status->value] = ['status' => $status, 'cards' => []];
        }

        foreach ($this->getBoard()->getCards() as $card) {
            $columns[$card->getStatus()->value]['cards'][] = $card;
        }

        foreach ($columns as &$column) {
            usort(
                $column['cards'],
                static fn (AmenityCard $a, AmenityCard $b) => $a->getPosition() <=> $b->getPosition(),
            );
        }

        return array_values($columns);
    }

    public function getTotalCount(): int
    {
        return $this->getBoard()->getCards()->count();
    }

    public function getCompletedCount(): int
    {
        return $this->getBoard()->getCards()
            ->filter(static fn (AmenityCard $card) => $card->getStatus() === AmenityCardStatus::COMPLETED)
            ->count();
    }

    public function getEditCard(): ?AmenityCard
    {
        return $this->findCard($this->editCardId ?? 0);
    }

    protected function instantiateForm(): FormInterface
    {
        $card = $this->findCard($this->editCardId ?? 0);

        if ($card === null) {
            $card = new AmenityCard();
            $status = AmenityCardStatus::tryFrom($this->createStatus ?? '') ?? AmenityCardStatus::NOT_STARTED;
            $card->setStatus($status);
        }

        return $this->createForm(AmenityCardType::class, $card);
    }

    private function findCard(int $cardId): ?AmenityCard
    {
        $card = $this->cardRepository->find($cardId);
        if ($card === null || $card->getBoard()->getId() !== $this->getBoard()->getId()) {
            return null;
        }

        return $card;
    }

    private function nextPosition(AmenityBoard $board, AmenityCardStatus $status): int
    {
        return $board->getCards()
            ->filter(static fn (AmenityCard $card) => $card->getStatus() === $status)
            ->count();
    }

    private function denyUnlessActive(): void
    {
        if ($this->isReadOnly()) {
            throw new AccessDeniedException('This board is archived.');
        }
    }

    private function denyUnlessCanManage(): void
    {
        if (!$this->canManage()) {
            throw new AccessDeniedException('You cannot manage amenities.');
        }
    }
}
