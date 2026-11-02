<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\BarStyle;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Aureum\BarStyleTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.bars.manage')]
class BarStyleTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public int $hotelId;

    protected function getEntityClass(): string
    {
        return BarStyle::class;
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
            ->addColumn('name', [
                'field' => 'name',
                'sortable' => true,
                'searchable' => true,
            ])
        ;
    }
}
