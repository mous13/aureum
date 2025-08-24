<?php

namespace Citadel\Aureum\Admin\Component;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('HotelTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('aureum.admin.hotels.manage')]
class HotelTable extends AbstractDoctrineTable
{
    public function __construct(
        private readonly HotelRepository $hotelRepository,
    ){
    }

    protected function getEntityClass(): string
    {
        return Hotel::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
                'sortable' => true,
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => $this->renderActions(...),
                'searchable' => false,
                'sortable' => false,
            ]);
    }

    private function renderActions(int $id): string
    {
        $actions = '';
        if($this->security->isGranted('aureum.admin.hotels.manage')) {
            $actions .= $this->renderAction('aureum_admin_employees_list_by_hotel', ['hotelId' => $id], 'person');
        }
        if($this->security->isGranted('aureum.admin.hotels.manage')) {
            $actions .= $this->renderAction('aureum_admin_hotels_edit', ['identifier' => $id], 'pencil-simple-line');
        }
        if($this->security->isGranted('aureum.admin.hotels.delete')) {
            $actions .= $this->renderAction('aureum_admin_hotels_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}