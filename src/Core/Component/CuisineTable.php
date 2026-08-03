<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Cuisine;
use Citadel\Aureum\Core\Repository\CuisineRepository;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Aureum\CuisineTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.restaurants.manage')]
class CuisineTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public int $hotelId;

    public function __construct(
        private readonly CuisineRepository $cuisineRepository,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Cuisine::class;
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
