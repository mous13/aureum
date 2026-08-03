<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Tag;
use Citadel\Aureum\Core\Repository\TagRepository;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Doctrine\ORM\QueryBuilder;

#[AsLiveComponent('Aureum\TagTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.restaurants.manage')]
class TagTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public int $hotelId;

    public function __construct(
        private readonly TagRepository $tagRepository,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Tag::class;
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
