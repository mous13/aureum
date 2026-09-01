<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Entity\Enum\DietaryRequirements;
use Citadel\Aureum\Core\Entity\Enum\MealPeriods;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Form\RestaurantType;
use Citadel\Aureum\Core\Repository\EventRepository;
use Citadel\Aureum\Core\Repository\RestaurantLogRepository;
use Citadel\Aureum\Core\Service\OpeningTimesStatus;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

#[AsLiveComponent('RestaurantTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.restaurants.view')]
class RestaurantTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public int $hotelId;

    private ?array $restaurantWithEvents = null;

    private array $statusCache = [];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RestaurantLogRepository $restaurantLogRepository,
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
        return Restaurant::class;
    }

    protected function getData(): array
    {
        $direction = array_filter($this->sort)['open'] ?? null;
        if ($direction === null) {
            return parent::getData();
        }

        $restaurants = $this->getQuery(array_filter($this->search))->getQuery()->getResult();
        usort($restaurants, fn(Restaurant $a, Restaurant $b) => $direction === self::SORT_DESC
            ? $this->rankFor($b) <=> $this->rankFor($a)
            : $this->rankFor($a) <=> $this->rankFor($b));

        return array_slice($restaurants, ($this->page - 1) * $this->limit, $this->limit);
    }

    private function rankFor(Restaurant $restaurant): int
    {
        return $this->openingTimesStatus->rankFromStatus($this->statusFor($restaurant));
    }

    private function statusFor(Restaurant $restaurant): ?bool
    {
        $id = $restaurant->getId();
        if (!array_key_exists($id, $this->statusCache)) {
            $this->statusCache[$id] = $this->openingTimesStatus->isOpenNow(
                $restaurant->getOpeningTimes(),
                $restaurant->getHotel()->getTimezone(),
            );
        }

        return $this->statusCache[$id];
    }

    private const SEARCHABLE_JOINS = [
        'tags' => ['join' => 'e.tags', 'alias' => 't', 'field' => 't.name'],
        'cuisines' => ['join' => 'e.cuisines', 'alias' => 'c', 'field' => 'c.name'],
    ];

    private const SEARCHABLE_JSON = [
        'mealPeriods',
        'dietaryRequirements',
    ];

    protected function getQuery(array $search): \Doctrine\ORM\QueryBuilder
    {
        $specialSearches = [];
        foreach ([...array_keys(self::SEARCHABLE_JOINS), ...self::SEARCHABLE_JSON] as $field) {
            if (empty($search[$field])) {
                continue;
            }

            $specialSearches[$field] = $search[$field];
            unset($search[$field]);
        }

        $qb = parent::getQuery($search);
        $qb
            ->andWhere('e.hotel = :hotel')
            ->setParameter('hotel', $this->hotelId)
            ->orderBy('e.name', 'ASC');

        foreach (self::SEARCHABLE_JOINS as $field => $config) {
            if (!empty($specialSearches[$field])) {
                $qb
                    ->leftJoin($config['join'], $config['alias'])
                    ->andWhere('LOWER(' . $config['field'] . ') LIKE LOWER(:' . $field . ')')
                    ->setParameter($field, '%' . $specialSearches[$field] . '%');
            }
        }

        foreach (self::SEARCHABLE_JSON as $field) {
            if (!empty($specialSearches[$field])) {
                $qb
                    ->andWhere('LOWER(e.' . $field . ') LIKE LOWER(:' . $field . ')')
                    ->setParameter($field, '%' . $specialSearches[$field] . '%');
            }
        }

        return $qb;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('open', [
                'field' => 'id',
                'label' => '',
                'sortable' => true,
                'searchable' => false,
                'renderer' => fn($id, Restaurant $restaurant) => $this->renderOpeningTimes($restaurant),
            ])
            ->addColumn('name', [
                'field' => 'name',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn($name, Restaurant $restaurant) => $this->renderName($name, $restaurant),

            ])
            ->addColumn('cuisines', [
                'field' => 'cuisines',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn($cuisines) => $this->renderBoxes(
                    $cuisines->map(fn($cuisine) => $cuisine->getName())->toArray()
                ),
            ])
            ->addColumn('neighbourhood', [
                'field' => 'neighbourhood',
                'sortable' => false,
                'searchable' => true,
            ])
            ->addColumn('street', [
                'field' => 'street',
                'sortable' => false,
                'searchable' => true,
            ])
            ->addColumn('Meals', [
                'field' => 'mealPeriods',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn($periods) => $this->renderBoxes(array_map(
                    fn($p) => $p instanceof MealPeriods ? $p->value : $p,
                    $periods ?? []
                )),
            ])
            ->addColumn('dietary requirements', [
                'field' => 'dietaryRequirements',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn($requirements) => $this->renderBoxes(array_map(
                    fn($r) => $r instanceof DietaryRequirements ? $r->value : $r,
                    $requirements ?? []
                )),
            ])
            ->addColumn('tags', [
                'field' => 'tags',
                'sortable' => false,
                'searchable' => true,
                'renderer' => fn($tags) => $this->renderBoxes(
                    $tags->map(fn($tag) => $tag->getName())->toArray()
                ),
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => fn($id, Restaurant $restaurant) => $this->renderactions($id, $restaurant),
                'searchable' => false,
                'sortable' => false,
            ])
        ;
    }

    private function renderActions(int $id, Restaurant $restaurant): string
    {
        $actions = '';

        $actions .= $this->renderLogsModal($restaurant);
        $actions .= $this->renderEditModal($id, $restaurant);

        return $actions;
    }

    private function renderBoxes(array $items): string
    {
        $boxes = implode('', array_map(
            fn($item) => '<div class="text-small">' . htmlspecialchars((string)$item, ENT_QUOTES) . '</div>',
            $items
        ));

        return '<div class="grid-3 gap-2">' . $boxes . '</div>';
    }

    private function renderName(string $name, Restaurant $restaurant): string
    {
        $name = htmlspecialchars($name, ENT_QUOTES);
        if ($this->hasUpcomingEvent($restaurant)) {
            $name .= ' <i class="ph-fill ph-tag text-luxury-light-alternative"></i>';
        }

        $connections = $restaurant->getConnections();
        if ($connections->isEmpty()) {
            return $name;
        }

        $conciergeNames = implode(',', $connections->map(
            fn($employee) => $employee->getName()
        )->toArray());

        return sprintf(
            '%s <i class="ph-fill ph-sparkle" style="color:#1a3f5f;" title="%s"></i>',
            $name,
            htmlspecialchars($conciergeNames)
        );
    }

    private function renderOpeningTimes(Restaurant $restaurant): string
    {
        $timezone = $restaurant->getHotel()->getTimezone();
        $isOpen = $this->statusFor($restaurant);

        $todayKey = null;
        if ($timezone !== null && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $todayKey = strtolower((new \DateTimeImmutable('now', new \DateTimeZone($timezone)))->format('D'));
        }

        return $this->twig->render('@CitadelAureum/core/restaurants/blocks/opening_times_modal.html.twig', [
            'restaurant' => $restaurant,
            'isOpen' => $isOpen,
            'todayKey' => $todayKey,
        ]);
    }

    private function renderLogsModal(Restaurant $restaurant): string
    {
        $logs = $this->restaurantLogRepository->findByRestaurant($restaurant);

        return $this->twig->render('@CitadelAureum/core/restaurants/blocks/logs_modal.html.twig', [
            'restaurant' => $restaurant,
            'logs' => $logs,
        ]);
    }

    private function renderEditModal(int $id, Restaurant $restaurant): string
    {
        $hotel = $restaurant->getHotel();

        $editForm = $this->formFactory->create(RestaurantType::class, $restaurant, [
            'hotel' => $hotel,
            'action' => $this->urlGenerator->generate('aureum_restaurants_edit', ['id' => $id]),
        ]);

        $googleEnabled = $hotel->isGooglePlacesEnabled() && $this->keyManager->hasKey();

        return $this->twig->render('@CitadelAureum/core/restaurants/blocks/edit_modal.html.twig', [
            'restaurant' => $restaurant,
            'editForm' => $editForm->createView(),
            'googleEnabled' => $googleEnabled,
            'googleSearchUrl' => $this->urlGenerator->generate('aureum_restaurants_google_search', ['id' => $id]),
            'googleLinkUrl' => $this->urlGenerator->generate('aureum_restaurants_google_link', ['id' => $id]),
            'googleUnlinkUrl' => $this->urlGenerator->generate('aureum_restaurants_google_unlink', ['id' => $id]),
            'googleCsrfToken' => $this->csrfTokenManager->getToken('aureum_restaurant_google')->getValue(),
        ]);
    }

    private function hasUpcomingEvent(Restaurant $restaurant): bool
    {
        if ($this->restaurantWithEvents === null) {
            $this->restaurantWithEvents = $this->eventRepository->findRestaurantsWithActiveEvents($restaurant->getHotel());
        }

        return in_array($restaurant->getId(), $this->restaurantWithEvents, true);
    }
}
