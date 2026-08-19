<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Component;

use Citadel\Aureum\Core\Entity\Announcement;
use Citadel\Aureum\Core\Entity\Enum\AnnouncementSeverity;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Aureum\AnnouncementTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.admin.announcements.manage')]
class AnnouncementTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return Announcement::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('title', [
                'field' => 'title',
                'sortable' => true,
                'searchable' => true,
                'renderer' => static fn (mixed $title): string => htmlspecialchars((string)$title, ENT_QUOTES),
            ])
            ->addColumn('module', [
                'field' => 'module',
                'sortable' => true,
                'searchable' => false,
                'renderer' => static fn (mixed $module): string => $module instanceof Module
                    ? $module->getLabel()
                    : '',
            ])
            ->addColumn('severity', [
                'field' => 'severity',
                'sortable' => true,
                'searchable' => false,
                'renderer' => static fn (mixed $severity): string => $severity instanceof AnnouncementSeverity
                    ? $severity->getLabel()
                    : '',
            ])
            ->addColumn('createdAt', [
                'field' => 'createdAt',
                'label' => 'Created',
                'sortable' => true,
                'searchable' => false,
                'renderer' => static fn (mixed $date): string => $date instanceof \DateTimeInterface
                    ? $date->format('d/m/Y H:i')
                    : '',
            ])
            ->addActionColumn(fn (mixed $id): string => $this->renderAction(
                'aureum_admin_announcements_edit',
                ['identifier' => $id],
                'pencil-simple-line',
            ) . $this->renderAction(
                'aureum_admin_announcements_delete',
                ['identifier' => $id],
                'trash',
            ))
        ;
    }
}
