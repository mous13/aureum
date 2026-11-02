<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Component\Trait\TableSearchTrait;
use Citadel\Aureum\Core\Controller\BarGoogleController;
use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Entity\Enum\DressCode;
use Citadel\Aureum\Core\Entity\Enum\EntryPolicy;
use Citadel\Aureum\Core\Form\BarType;
use Citadel\Aureum\Core\Repository\BarLogRepository;
use Citadel\Aureum\Core\Repository\EventRepository;
use Citadel\Aureum\Core\Service\OpeningTimesStatus;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

#[AsLiveComponent('BarTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.bars.view')]
class BarTable extends AbstractDoctrineTable
{
    use TableSearchTrait;

    private const SEARCH_FIELDS = [
        'name' => 'e.name',
        'neighbourhood' => 'e.neighbourhood',
        'street' => 'e.street',
        'happyHour' => 'e.happyHour',
    ];

    private const SEARCHABLE_RELATIONS = ['styles', 'tags'];

    private const SEARCHABLE_ENUMS = [
        'dressCode' => DressCode::class,
        'entryPolicy' => EntryPolicy::class,
    ];

    #[LiveProp]
    public array $sort = ['name' => self::SORT_ASC];

    #[LiveProp]
    public int $hotelId;

    private ?array $barsWithEvents = null;

    private array $statusCache = [];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly BarLogRepository $barLogRepository,
        private readonly Environment $twig,
        private readonly FormFactoryInterface $formFactory,
        private readonly EventRepository $eventRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly GooglePlacesKeyManager $keyManager,
        private readonly OpeningTimesStatus $openingTimesStatus,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Bar::class;
    }

    protected function getData(): array
    {
        $direction = array_filter($this->sort)['open'] ?? null;
        if ($direction === null) {
            return parent::getData();
        }

        $bars = $this->getQuery(array_filter($this->search))->getQuery()->getResult();
        usort($bars, fn (Bar $a, Bar $b) => $direction === self::SORT_DESC
            ? $this->rankFor($b) <=> $this->rankFor($a)
            : $this->rankFor($a) <=> $this->rankFor($b));

        return array_slice($bars, ($this->page - 1) * $this->limit, $this->limit);
    }

    protected function getQuery(array $search): QueryBuilder
    {
        $qb = parent::getQuery([])
            ->andWhere('e.hotel = :hotel')
            ->setParameter('hotel', $this->hotelId);

        foreach (self::SEARCHABLE_RELATIONS as $relation) {
            $term = trim((string)($search[$relation] ?? ''));
            if ($term === '') {
                continue;
            }

            $matchingBars = $qb->getEntityManager()->createQueryBuilder()
                ->select("{$relation}_bar.id")
                ->from(Bar::class, "{$relation}_bar")
                ->join("{$relation}_bar.{$relation}", "{$relation}_item")
                ->where("LOWER({$relation}_item.name) LIKE LOWER(:{$relation})");

            $qb
                ->andWhere($qb->expr()->in('e.id', $matchingBars->getDQL()))
                ->setParameter($relation, '%' . $term . '%');
        }

        foreach (self::SEARCHABLE_ENUMS as $field => $enumClass) {
            $term = mb_strtolower(trim((string)($search[$field] ?? '')));
            if ($term === '') {
                continue;
            }

            $matches = array_values(array_filter(
                $enumClass::cases(),
                static fn (DressCode|EntryPolicy $case) => str_contains(mb_strtolower($case->getLabel()), $term),
            ));

            $qb
                ->andWhere("e.{$field} IN (:{$field})")
                ->setParameter($field, $matches === [] ? [''] : array_map(static fn ($case) => $case->value, $matches));
        }

        return $this->applyTextSearch($qb, $search, self::SEARCH_FIELDS);
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('open', [
                'field' => 'id',
                'label' => 'Open',
                'sortable' => true,
                'searchable' => false,
                'renderer' => fn ($id, Bar $bar) => $this->renderOpeningTimes($bar),
            ])
            ->addColumn('name', [
                'label' => 'Name',
                'field' => 'name',
                'sortable' => true,
                'searchable' => true,
                'renderer' => fn ($name, Bar $bar) => $this->renderName($name, $bar),
            ])
            ->addColumn('styles', [
                'label' => 'Style',
                'field' => 'styles',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn ($styles) => $this->renderBoxes(
                    $styles->map(fn ($style) => $style->getName())->toArray()
                ),
            ])
            ->addColumn('neighbourhood', [
                'label' => 'Neighbourhood',
                'field' => 'neighbourhood',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('street', [
                'label' => 'Street',
                'field' => 'street',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('dressCode', [
                'label' => 'Dress Code',
                'field' => 'dressCode',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn (?DressCode $dressCode) => $this->renderText($dressCode?->getLabel()),
            ])
            ->addColumn('entryPolicy', [
                'label' => 'Entry',
                'field' => 'entryPolicy',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn (?EntryPolicy $policy) => $this->renderText($policy?->getLabel()),
            ])
            ->addColumn('happyHour', [
                'label' => 'Happy Hour',
                'field' => 'happyHour',
                'sortable' => false,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('tags', [
                'label' => 'Tags',
                'field' => 'tags',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn ($tags) => $this->renderBoxes(
                    $tags->map(fn ($tag) => $tag->getName())->toArray()
                ),
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => fn ($id, Bar $bar) => $this->renderActions($id, $bar),
                'searchable' => false,
                'sortable' => false,
            ])
        ;
    }

    private function rankFor(Bar $bar): int
    {
        return $this->openingTimesStatus->rankFromStatus($this->statusFor($bar));
    }

    private function statusFor(Bar $bar): ?bool
    {
        $id = $bar->getId();
        if (!array_key_exists($id, $this->statusCache)) {
            $this->statusCache[$id] = $this->openingTimesStatus->isOpenNow(
                $bar->getOpeningTimes(),
                $bar->getHotel()->getTimezone(),
            );
        }

        return $this->statusCache[$id];
    }

    private function renderActions(int $id, Bar $bar): string
    {
        $actions = $this->renderLogsModal($bar);
        if ($this->isGranted('aureum.module.bars.manage')) {
            $actions .= $this->renderEditModal($id, $bar);
        }

        return $actions;
    }

    private function renderBoxes(array $items): string
    {
        $boxes = implode('', array_map(
            fn ($item) => '<div class="text-small">' . htmlspecialchars((string)$item, ENT_QUOTES) . '</div>',
            $items
        ));

        return '<div class="grid-3 gap-2">' . $boxes . '</div>';
    }

    private function renderName(string $name, Bar $bar): string
    {
        $name = htmlspecialchars($name, ENT_QUOTES);
        if ($this->hasEventToday($bar)) {
            $name .= ' <i class="ph-fill ph-tag text-luxury-light-alternative"></i>';
        }

        $connections = $bar->getConnections();
        if ($connections->isEmpty()) {
            return $name;
        }

        $conciergeNames = implode(',', $connections->map(
            fn ($employee) => $employee->getName()
        )->toArray());

        return sprintf(
            '%s <i class="ph-fill ph-sparkle" style="color:#1a3f5f;" title="%s"></i>',
            $name,
            htmlspecialchars($conciergeNames, ENT_QUOTES)
        );
    }

    private function renderOpeningTimes(Bar $bar): string
    {
        $timezone = $bar->getHotel()->getTimezone();

        $todayKey = null;
        if ($timezone !== null && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $todayKey = strtolower((new \DateTimeImmutable('now', new \DateTimeZone($timezone)))->format('D'));
        }

        return $this->twig->render('@CitadelAureum/core/venues/blocks/opening_times_modal.html.twig', [
            'venue' => $bar,
            'isOpen' => $this->statusFor($bar),
            'todayKey' => $todayKey,
        ]);
    }

    private function renderLogsModal(Bar $bar): string
    {
        return $this->twig->render('@CitadelAureum/core/venues/blocks/logs_modal.html.twig', [
            'venue' => $bar,
            'logs' => $this->barLogRepository->findByBar($bar),
        ]);
    }

    private function renderEditModal(int $id, Bar $bar): string
    {
        $hotel = $bar->getHotel();

        $editForm = $this->formFactory->createNamed('bar_' . $id, BarType::class, $bar, [
            'hotel' => $hotel,
            'action' => $this->urlGenerator->generate('aureum_bars_edit', ['id' => $id]),
        ]);

        return $this->twig->render('@CitadelAureum/core/bars/blocks/edit_modal.html.twig', [
            'bar' => $bar,
            'editForm' => $editForm->createView(),
            'googleEnabled' => $hotel->isGooglePlacesEnabled() && $this->keyManager->hasKey(),
            'googleSearchUrl' => $this->urlGenerator->generate('aureum_bars_google_search', ['id' => $id]),
            'googleLinkUrl' => $this->urlGenerator->generate('aureum_bars_google_link', ['id' => $id]),
            'googleUnlinkUrl' => $this->urlGenerator->generate('aureum_bars_google_unlink', ['id' => $id]),
            'googleCsrfToken' => $this->csrfTokenManager->getToken(BarGoogleController::CSRF_TOKEN_ID)->getValue(),
        ]);
    }

    private function hasEventToday(Bar $bar): bool
    {
        if ($this->barsWithEvents === null) {
            $this->barsWithEvents = $this->eventRepository->findBarsWithActiveEvents($bar->getHotel());
        }

        return in_array($bar->getId(), $this->barsWithEvents, true);
    }
}
