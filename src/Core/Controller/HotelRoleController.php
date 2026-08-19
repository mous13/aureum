<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Controller\Trait\HotelScopedCrudTrait;
use Citadel\Aureum\Core\Entity\HotelRole;
use Citadel\Aureum\Core\Form\HotelRoleType;
use Citadel\Aureum\Core\Security\AureumVoter;
use Citadel\Aureum\Core\Service\AureumService;
use Forumify\Admin\Crud\AbstractCrudController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/roles', 'roles')]
#[IsGranted(AureumVoter::RBAC_MANAGE)]
class HotelRoleController extends AbstractCrudController
{
    use HotelScopedCrudTrait;

    public function __construct(
        private readonly AureumService $aureumService,
    ) {
    }

    protected string $listTemplate = '@CitadelAureum/core/components/list.html.twig';
    protected string $formTemplate = '@CitadelAureum/core/roles/form.html.twig';
    protected string $deleteTemplate = '@CitadelAureum/core/components/delete.html.twig';

    protected function getTranslationPrefix(): string
    {
        return 'aureum.hotel_role.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return HotelRole::class;
    }

    protected function getTableName(): string
    {
        return 'Aureum\\HotelRoleTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(HotelRoleType::class, $data, [
            'hotel' => $this->aureumService->getHotel(),
        ]);
    }

    #[Route('/list', name: '_list')]
    public function list(): Response
    {
        return $this->render($this->listTemplate, $this->templateParams([
            'table' => $this->getTableName(),
            'hotelId' => $this->aureumService->getHotel()->getId(),
        ]));
    }

    protected function getAureumService(): AureumService
    {
        return $this->aureumService;
    }

    protected function save(bool $isNew, FormInterface $form): object
    {
        $role = $form->getData();

        if ($isNew && $role instanceof HotelRole) {
            $role->setHotel($this->aureumService->getHotel());
        }

        return parent::save($isNew, $form);
    }
}
