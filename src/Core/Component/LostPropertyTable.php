<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Component\Trait\TableSearchTrait;
use Citadel\Aureum\Core\Entity\Enum\LostPropertyClass;
use Citadel\Aureum\Core\Entity\Enum\LostPropertyStatus;
use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Form\LostPropertyEditType;
use Citadel\Aureum\Core\Repository\LostPropertyLogRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

#[AsLiveComponent('Aureum\LostPropertyTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.lost_property.view')]
class LostPropertyTable extends AbstractDoctrineTable
{
    use TableSearchTrait;

    private const SEARCH_FIELDS = [
        'description' => 'e.description',
        'location' => 'e.location',
        'storedLocation' => 'e.storedLocation',
        'reportedBy' => 'search_reported_by.name',
    ];

    #[LiveProp]
    public array $sort = ['date' => self::SORT_DESC];

    #[LiveProp]
    public int $hotelId;

    #[LiveProp]
    public array $statuses = [];

    public function __construct(
        private readonly LostPropertyLogRepository $lostPropertyLogRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FormFactoryInterface $formFactory,
        private readonly AureumService $aureumService,
        private readonly Environment $twig,
    ) {
    }

    protected function getEntityClass(): string
    {
        return LostProperty::class;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        $qb = parent::getQuery([])
            ->leftJoin('e.reportedBy', 'search_reported_by')
            ->andWhere('e.hotel = :hotel')
            ->setParameter('hotel', $this->hotelId);

        if ($this->statuses !== []) {
            $qb
                ->andWhere('e.status IN (:statuses)')
                ->setParameter('statuses', array_map(
                    static fn (string $status) => LostPropertyStatus::from($status),
                    $this->statuses,
                ));
        }

        $typeSearch = mb_strtolower(trim((string)($search['type'] ?? '')));
        if ($typeSearch !== '') {
            $types = array_values(array_filter(
                LostPropertyClass::cases(),
                static fn (LostPropertyClass $type) => str_contains(mb_strtolower($type->getLabel()), $typeSearch),
            ));
            if ($types === []) {
                return $qb->andWhere('1 = 0');
            }

            $qb
                ->andWhere('e.type IN (:types)')
                ->setParameter('types', $types);
        }

        $guestSearch = trim((string)($search['guest'] ?? ''));
        if ($guestSearch !== '') {
            $qb
                ->andWhere($qb->expr()->orX(
                    'LOWER(e.guest) LIKE LOWER(:search_guest)',
                    'LOWER(e.contact) LIKE LOWER(:search_guest)',
                ))
                ->setParameter('search_guest', '%' . $guestSearch . '%');
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
            ->addColumn('type', [
                'label' => 'Type',
                'field' => 'type',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderType(...),
            ])
            ->addColumn('guest', [
                'label' => 'Guest',
                'field' => 'guest',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderGuest(...),
            ])
            ->addColumn('description', [
                'label' => 'Description',
                'field' => 'description',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('location', [
                'label' => 'Location',
                'field' => 'location',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('storedLocation', [
                'label' => 'Stored Location',
                'field' => 'storedLocation',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('reportedBy', [
                'label' => 'Reported By',
                'field' => 'reportedBy.name',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('status', [
                'label' => 'Status',
                'field' => 'status',
                'sortable' => true,
                'searchable' => false,
                'renderer' => static fn (LostPropertyStatus $status) => htmlspecialchars(ucfirst($status->getLabel()), ENT_QUOTES),
            ])
            ->addColumn('actions', [
                'label' => '',
                'field' => 'id',
                'sortable' => false,
                'searchable' => false,
                'renderer' => $this->renderActions(...),
            ]);
    }

    private function renderDate(?\DateTimeInterface $date): string
    {
        return $this->renderLocalDate($date, $this->aureumService->getEmployee()?->getUser()?->getTimezone() ?? 'UTC');
    }

    private function renderType(LostPropertyClass $type): string
    {
        return sprintf(
            '<i class="ph %s"></i> %s',
            $type === LostPropertyClass::LOST ? 'ph-map-pin-line' : 'ph-check-circle',
            htmlspecialchars($type->getLabel(), ENT_QUOTES),
        );
    }

    private function renderGuest(?string $guest, LostProperty $property): string
    {
        $rendered = sprintf('<div>%s</div>', $this->renderText($guest));
        if ($property->getContact() !== null && $property->getContact() !== '') {
            $rendered .= sprintf('<div class="text-small">%s</div>', $this->renderText($property->getContact()));
        }

        return $rendered;
    }

    private function renderActions(mixed $id, LostProperty $property): string
    {
        $actions = $this->twig->render('@CitadelAureum/core/lostproperty/blocks/lost_property_notes.html.twig', [
            'property' => $property,
            'logs' => $this->lostPropertyLogRepository->findByLostProperty($property),
        ]);

        if ($this->security->isGranted('aureum.module.lost_property.manage')) {
            $editForm = $this->formFactory->createNamed('lost_property_edit_' . $property->getId(), LostPropertyEditType::class, $property, [
                'action' => $this->urlGenerator->generate('aureum_lost_property_edit', ['id' => $property->getId()]),
            ]);

            $actions .= $this->twig->render('@CitadelAureum/core/lostproperty/blocks/lost_property_edit.html.twig', [
                'property' => $property,
                'editForm' => $editForm->createView(),
            ]);
        }

        return '<div class="flex items-center justify-end">' . $actions . '</div>';
    }
}
