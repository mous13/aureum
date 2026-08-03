<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\RoomType;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Aureum\RoomTypeTable', '@CitadelAureum/core/components/table.html.twig')]
class RoomTypeTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public int $hotelId;

    protected function getEntityClass(): string
    {
        return RoomType::class;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        return parent::getQuery($search)
            ->andWhere('e.hotel = :hotel')
            ->setParameter('hotel', $this->hotelId);
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('code', [
                'field' => 'code',
                'sortable' => true,
                'searchable' => true,
            ])
            ->addColumn('name', [
                'field' => 'name',
                'sortable' => true,
                'searchable' => true,
            ])
            ->addColumn('archived', [
                'field' => 'archived',
                'sortable' => true,
                'searchable' => false,
                'renderer' => static fn (mixed $archived): string => $archived ? 'Yes' : 'No',
            ])
            ->addActionColumn(fn (mixed $id): string => $this->renderAction(
                'aureum_room_types_edit',
                ['identifier' => $id],
                'pencil-simple-line',
            ))
        ;
    }
}
