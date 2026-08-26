<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Controller\Trait\HotelScopedCrudTrait;
use Citadel\Aureum\Core\Entity\SopCategory;
use Citadel\Aureum\Core\Form\SopCategoryType;
use Citadel\Aureum\Core\Service\AureumService;
use Forumify\Admin\Crud\AbstractCrudController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<SopCategory>
 */
#[Route('/sops/categories', 'sop_categories')]
#[IsGranted('aureum.module.sops.manage')]
class SopCategoryController extends AbstractCrudController
{
    use HotelScopedCrudTrait;

    public function __construct(
        private readonly AureumService $aureumService,
    ) {
    }

    protected string $listTemplate = '@CitadelAureum/core/components/list.html.twig';
    protected string $deleteTemplate = '@CitadelAureum/core/components/delete.html.twig';
    protected string $formTemplate = '@CitadelAureum/core/components/form.html.twig';

    protected function getAureumService(): AureumService
    {
        return $this->aureumService;
    }

    protected function getTranslationPrefix(): string
    {
        return 'aureum.sop_category.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return SopCategory::class;
    }

    protected function getTableName(): string
    {
        return 'Aureum\\SopCategoryTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        if ($data === null) {
            $data = new SopCategory();
            $data->setHotel($this->aureumService->getHotel());
        }

        return $this->createForm(SopCategoryType::class, $data);
    }

    #[Route('/list', name: '_list')]
    public function list(): Response
    {
        return $this->render($this->listTemplate, $this->templateParams([
            'table' => $this->getTableName(),
            'hotelId' => $this->aureumService->getHotel()->getId(),
        ]));
    }
}
