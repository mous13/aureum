<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Component\Trait\TableSearchTrait;
use Citadel\Aureum\Core\Entity\Enum\FineStatus;
use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Form\FineEditType;
use Citadel\Aureum\Core\Repository\FineLogRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

#[AsLiveComponent('Aureum\FineTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.fines.view')]
class FineTable extends AbstractDoctrineTable
{
    use TableSearchTrait;

    private const SEARCH_FIELDS = [
        'number' => 'e.number',
        'name' => 'e.name',
        'email' => 'e.email',
        'createdBy' => 'search_created_by.name',
    ];

    #[LiveProp]
    public array $sort = ['date' => self::SORT_DESC];

    #[LiveProp]
    public int $hotelId;

    #[LiveProp]
    public array $statuses = [];

    public function __construct(
        private readonly FineLogRepository $fineLogRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FormFactoryInterface $formFactory,
        private readonly AureumService $aureumService,
        private readonly Environment $twig,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Fine::class;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        $qb = parent::getQuery([])
            ->leftJoin('e.createdBy', 'search_created_by')
            ->andWhere('e.hotel = :hotel')
            ->setParameter('hotel', $this->hotelId);

        if ($this->statuses !== []) {
            $qb
                ->andWhere('e.status IN (:statuses)')
                ->setParameter('statuses', array_map(
                    static fn (string $status) => FineStatus::from($status),
                    $this->statuses,
                ));
        }

        return $this->applyTextSearch($qb, $search, self::SEARCH_FIELDS);
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('date', [
                'label' => 'Date',
                'field' => 'createdAt',
                'sortable' => true,
                'searchable' => false,
                'renderer' => $this->renderDate(...),
            ])
            ->addColumn('number', [
                'label' => 'Fine Number',
                'field' => 'number',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('name', [
                'label' => 'Guest Name',
                'field' => 'name',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('email', [
                'label' => 'Guest Email',
                'field' => 'email',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('createdBy', [
                'label' => 'Logged By',
                'field' => 'createdBy.name',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('status', [
                'label' => 'Status',
                'field' => 'status',
                'sortable' => true,
                'searchable' => false,
                'renderer' => static fn (FineStatus $status) => htmlspecialchars($status->getLabel(), ENT_QUOTES),
            ])
            ->addColumn('actions', [
                'label' => '',
                'field' => 'id',
                'sortable' => false,
                'searchable' => false,
                'renderer' => $this->renderActions(...),
            ]);
    }

    private function renderDate(?\DateTimeInterface $createdAt, Fine $fine): string
    {
        $date = $fine->getStatus() === FineStatus::APPEAL_COMPLETED ? ($fine->getUpdatedAt() ?? $createdAt) : $createdAt;

        return $this->renderLocalDate($date, $this->aureumService->getEmployee()?->getUser()?->getTimezone() ?? 'UTC');
    }

    private function renderActions(mixed $id, Fine $fine): string
    {
        $actions = $this->twig->render('@CitadelAureum/core/fines/blocks/fine_notes.html.twig', [
            'fine' => $fine,
            'logs' => $this->fineLogRepository->findByFine($fine),
        ]);

        if ($fine->getStatus() !== FineStatus::APPEAL_COMPLETED && $this->security->isGranted('aureum.module.fines.manage')) {
            $editForm = $this->formFactory->createNamed('fine_edit_' . $fine->getId(), FineEditType::class, $fine, [
                'action' => $this->urlGenerator->generate('aureum_fines_edit', ['id' => $fine->getId()]),
            ]);

            $actions .= $this->twig->render('@CitadelAureum/core/fines/blocks/fines_edit.html.twig', [
                'fine' => $fine,
                'editForm' => $editForm->createView(),
            ]);
        }

        return '<div class="flex items-center justify-end">' . $actions . '</div>';
    }
}
