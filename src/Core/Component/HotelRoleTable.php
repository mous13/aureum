<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\HotelRole;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Aureum\HotelRoleTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.rbac.manage')]
class HotelRoleTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public int $hotelId;

    protected function getEntityClass(): string
    {
        return HotelRole::class;
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
                'renderer' => static fn (mixed $name): string => htmlspecialchars((string)$name, ENT_QUOTES),
            ])
            ->addColumn('permissions', [
                'field' => 'permissions',
                'sortable' => false,
                'searchable' => false,
                'renderer' => static fn (mixed $permissions): string => htmlspecialchars(
                    implode(', ', (array)$permissions),
                    ENT_QUOTES,
                ),
            ])
            ->addActionColumn(fn (mixed $id): string => $this->renderAction(
                'aureum_roles_edit',
                ['identifier' => $id],
                'pencil-simple-line',
            ) . $this->renderAction(
                'aureum_roles_delete',
                ['identifier' => $id],
                'trash',
            ))
        ;
    }
}
