<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Tag;
use Citadel\Aureum\Core\Form\TagType;
use Citadel\Aureum\Core\Service\AureumService;
use Forumify\Admin\Crud\AbstractCrudController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tags', 'tags')]
#[IsGranted('aureum.module.restaurants.manage')]
class TagController extends AbstractCrudController
{
    public function __construct(
        private readonly AureumService $aureumService,
    ) {
    }

    protected string $listTemplate = '@CitadelAureum/core/components/list.html.twig';
    protected string $formTemplate = '@CitadelAureum/core/components/form.html.twig';

    protected function getTranslationPrefix(): string
    {
        return 'aureum.tag.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Tag::class;
    }

    protected function getTableName(): string
    {
        return 'Aureum\\TagTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(TagType::class, $data);
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
        $tag = $form->getData();

        if ($isNew && $tag instanceof Tag) {
            $tag->setHotel($this->aureumService->getHotel());
        }

        return parent::save($isNew, $form);
    }
}
