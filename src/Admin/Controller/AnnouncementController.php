<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Controller;

use Citadel\Aureum\Admin\Form\AnnouncementType;
use Citadel\Aureum\Core\Entity\Announcement;
use Forumify\Admin\Crud\AbstractCrudController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/announcements', 'announcements')]
#[IsGranted('aureum.admin.announcements.view')]
class AnnouncementController extends AbstractCrudController
{
    protected ?string $permissionCreate = 'aureum.admin.announcements.manage';
    protected ?string $permissionEdit = 'aureum.admin.announcements.manage';
    protected ?string $permissionDelete = 'aureum.admin.announcements.manage';

    protected function getTranslationPrefix(): string
    {
        return 'aureum.announcement.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Announcement::class;
    }

    protected function getTableName(): string
    {
        return 'Aureum\\AnnouncementTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(AnnouncementType::class, $data);
    }
}
