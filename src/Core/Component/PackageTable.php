<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Component\Trait\TableSearchTrait;
use Citadel\Aureum\Core\Entity\Enum\PackageStatus;
use Citadel\Aureum\Core\Entity\Package;
use Citadel\Aureum\Core\Form\PackageEditType;
use Citadel\Aureum\Core\Repository\PackageLogRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

#[AsLiveComponent('Aureum\PackageTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.packages.view')]
class PackageTable extends AbstractDoctrineTable
{
    use TableSearchTrait;

    private const SEARCH_FIELDS = [
        'name' => 'e.name',
        'description' => 'e.description',
        'location' => 'e.location',
        'employee' => 'search_employee.name',
    ];

    #[LiveProp]
    public array $sort = ['date' => self::SORT_DESC];

    #[LiveProp]
    public int $hotelId;

    #[LiveProp]
    public array $statuses = [];

    public function __construct(
        private readonly PackageLogRepository $packageLogRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FormFactoryInterface $formFactory,
        private readonly AureumService $aureumService,
        private readonly Environment $twig,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Package::class;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        $qb = parent::getQuery([])
            ->leftJoin('e.employee', 'search_employee')
            ->andWhere('e.hotel = :hotel')
            ->setParameter('hotel', $this->hotelId);

        if ($this->statuses !== []) {
            $qb
                ->andWhere('e.status IN (:statuses)')
                ->setParameter('statuses', array_map(
                    static fn (string $status) => PackageStatus::from($status),
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
            ->addColumn('name', [
                'label' => 'Guest Name',
                'field' => 'name',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('description', [
                'label' => 'Package Description',
                'field' => 'description',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('location', [
                'label' => 'Location Stored',
                'field' => 'location',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('employee', [
                'label' => 'Logged By',
                'field' => 'employee.name',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('actions', [
                'label' => '',
                'field' => 'id',
                'sortable' => false,
                'searchable' => false,
                'renderer' => $this->renderActions(...),
            ]);
    }

    private function renderDate(?\DateTimeInterface $createdAt, Package $package): string
    {
        $date = $package->getStatus() === PackageStatus::PICKED_UP ? ($package->getUpdatedAt() ?? $createdAt) : $createdAt;

        return $this->renderLocalDate($date, $this->aureumService->getEmployee()?->getUser()?->getTimezone() ?? 'UTC');
    }

    private function renderActions(mixed $id, Package $package): string
    {
        $actions = $this->twig->render('@CitadelAureum/core/packages/blocks/package_notes.html.twig', [
            'package' => $package,
            'logs' => $this->packageLogRepository->findByPackage($package),
        ]);

        if ($this->security->isGranted('aureum.module.packages.manage')) {
            $editForm = $this->formFactory->createNamed('package_edit_' . $package->getId(), PackageEditType::class, $package, [
                'action' => $this->urlGenerator->generate('aureum_packages_edit', ['id' => $package->getId()]),
            ]);

            $actions .= $this->twig->render('@CitadelAureum/core/packages/blocks/packages_edit.html.twig', [
                'package' => $package,
                'editForm' => $editForm->createView(),
            ]);
        }

        return '<div class="flex items-center justify-end">' . $actions . '</div>';
    }
}
