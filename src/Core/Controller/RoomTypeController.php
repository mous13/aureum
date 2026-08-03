<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\RoomType;
use Citadel\Aureum\Core\Form\RoomTypeType;
use Citadel\Aureum\Core\Service\AureumService;
use Forumify\Admin\Crud\AbstractCrudController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rooms/types', 'room_types')]
class RoomTypeController extends AbstractCrudController
{
    public function __construct(
        private readonly AureumService $aureumService,
    ) {
    }

    protected string $listTemplate = '@CitadelAureum/core/components/list.html.twig';
    protected string $formTemplate = '@CitadelAureum/core/components/form.html.twig';

    protected bool $allowDelete = false;

    protected ?string $permissionView = 'aureum.core.rooms.manage';
    protected ?string $permissionCreate = 'aureum.core.rooms.manage';
    protected ?string $permissionEdit = 'aureum.core.rooms.manage';

    protected function getTranslationPrefix(): string
    {
        return 'aureum.room_type.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return RoomType::class;
    }

    protected function getTableName(): string
    {
        return 'Aureum\\RoomTypeTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(RoomTypeType::class, $data);
    }

    #[Route('/list', name: '_list')]
    public function list(): Response
    {
        $this->denyAccessUnlessGranted($this->permissionView);

        return $this->render($this->listTemplate, $this->templateParams([
            'table' => $this->getTableName(),
            'hotelId' => $this->aureumService->getHotel()->getId(),
        ]));
    }

    #[Route('/{identifier}/edit', name: '_edit')]
    public function edit(Request $request, string $identifier): Response
    {
        $this->denyUnlessSameHotel((int)$identifier);

        return parent::edit($request, $identifier);
    }

    protected function save(bool $isNew, FormInterface $form): object
    {
        $roomType = $form->getData();

        if ($isNew && $roomType instanceof RoomType) {
            $roomType->setHotel($this->aureumService->getHotel());
        }

        return parent::save($isNew, $form);
    }

    private function denyUnlessSameHotel(int $identifier): void
    {
        $roomType = $this->repository->find($identifier);
        $hotel = $this->aureumService->getHotel();

        if ($roomType instanceof RoomType && $roomType->getHotel()->getId() !== $hotel?->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
