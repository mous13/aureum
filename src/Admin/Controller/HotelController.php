<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Controller;

use Citadel\Aureum\Admin\Form\HotelType;
use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Admin\Crud\AbstractCrudController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hotels', 'hotels')]
#[IsGranted('aureum.admin.hotels.view')]
class HotelController extends AbstractCrudController
{
    protected function getTranslationPrefix(): string
    {
        return 'aureum.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Hotel::class;
    }

    protected function getTableName(): string
    {
        return 'HotelTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(HotelType::class, $data, [
            'image_required' => $data === null,
        ]);
    }
}
