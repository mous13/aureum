<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Enum\DietaryRequirements;
use Citadel\Aureum\Core\Entity\Enum\MealPeriods;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Form\RestaurantType;
use Citadel\Aureum\Core\Repository\EventRepository;
use Citadel\Aureum\Core\Repository\RestaurantLogRepository;
use Citadel\Aureum\Core\Repository\RestaurantRepository;
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

    public function __construct(
        private readonly RestaurantRepository $restaurantRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RestaurantLogRepository $restaurantLogRepository,
        private readonly Environment $twig,
        private readonly FormFactoryInterface $formFactory,
        private readonly EventRepository $eventRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Restaurant::class;
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
            ->orderBy('e.score', 'DESC');

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
            ->addColumn('rating', [
                'field' => 'score',
                'sortable' => false,
                'searchable' => false,
            ])
            ->addColumn('voting', [
                'field' => 'id',
                'label' => '',
                'sortable' => false,
                'searchable' => false,
                'renderer' => fn($id, Restaurant $restaurant) => $this->renderVoting($id, $restaurant),
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
            fn($item) => '<div class="text-small">' . $item . '</div>',
            $items
        ));

        return '<div class="grid-3 gap-2">' . $boxes . '</div>';
    }

    private function renderVoting(int $id, Restaurant $restaurant): string
    {
        $upUrl = $this->urlGenerator->generate('aureum_restaurants_upvote', ['id' => $id]);
        $downUrl = $this->urlGenerator->generate('aureum_restaurants_downvote', ['id' => $id]);
        $token = htmlspecialchars(
            $this->csrfTokenManager->getToken('aureum_restaurant_vote_' . $id)->getValue(),
            ENT_QUOTES,
        );

        return sprintf(
            '<div class="flex items-center justify-center">
            <form method="post" action="%s">
                <input type="hidden" name="_token" value="%s">
                <button type="submit" class="btn-link btn-icon btn-small" title="+2">
                    <i class="ph ph-thumbs-up"></i>
                </button>
            </form>
            <form method="post" action="%s">
                <input type="hidden" name="_token" value="%s">
                <button type="submit" class="btn-link btn-icon btn-small" title="-1">
                    <i class="ph ph-thumbs-down"></i>
                </button>
            </form>
        </div>',
            $upUrl,
            $token,
            $downUrl,
            $token
        );
    }

    private function renderName(string $name, Restaurant $restaurant): string
    {
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

        return $this->twig->render('@CitadelAureum/core/restaurants/blocks/edit_modal.html.twig', [
            'restaurant' => $restaurant,
            'editForm' => $editForm->createView(),
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
