<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Controller\Trait\HotelScopedCrudTrait;
use Citadel\Aureum\Core\Entity\Cuisine;
use Citadel\Aureum\Core\Form\CuisineType;
use Citadel\Aureum\Core\Service\AureumService;
use Forumify\Admin\Crud\AbstractCrudController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cuisines', 'cuisines')]
#[IsGranted('aureum.module.restaurants.manage')]
class CuisineController extends AbstractCrudController
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
        return 'aureum.cuisine.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Cuisine::class;
    }

    protected function getTableName(): string
    {
        return 'Aureum\\CuisineTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(CuisineType::class, $data);
    }

    #[Route('/list', name: '_list')]
    public function list(): Response
    {
        return $this->render($this->listTemplate, $this->templateParams([
            'table' => $this->getTableName(),
            'hotelId' => $this->aureumService->getHotel()->getId(),
        ]));
    }

    protected function save(bool $isNew, FormInterface $form): object
    {
        $cuisine = $form->getData();

        if ($isNew && $cuisine instanceof Cuisine) {
            $cuisine->setHotel($this->aureumService->getHotel());
        }

        return parent::save($isNew, $form);
    }
}
