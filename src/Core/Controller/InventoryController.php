<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Enum\ReorderStatus;
use Citadel\Aureum\Core\Repository\InventoryItemLogRepository;
use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\InventoryRepository;
use Citadel\Aureum\Core\Repository\StockCountLineRepository;
use Citadel\Aureum\Core\Repository\StockCountRepository;
use Citadel\Aureum\Core\Repository\StockMovementRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\Forecast\InventoryForecastService;
use Citadel\Aureum\Core\Service\Forecast\ItemForecast;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.inventory.view')]
class InventoryController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly InventoryRepository $inventoryRepository,
        private readonly InventoryItemRepository $itemRepository,
        private readonly StockCountRepository $stockCountRepository,
        private readonly InventoryForecastService $forecastService,
        private readonly StockCountLineRepository $countLineRepository,
        private readonly StockMovementRepository $movementRepository,
        private readonly InventoryItemLogRepository $itemLogRepository,
    ) {
    }

    #[Route('/inventory', name: 'inventory')]
    public function index(): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createAccessDeniedException();
        }

        $rows = [];
        foreach ($this->inventoryRepository->findActiveByHotel($hotel) as $inventory) {
            $latest = $this->stockCountRepository->findLatestForInventory($inventory);

            $rows[] = [
                'inventory' => $inventory,
                'itemCount' => count($this->itemRepository->findActiveByInventory($inventory)),
                'lastCountedAt' => $latest?->getCountedAt(),
                'lastCountedBy' => $latest?->getCountedBy(),
            ];
        }

        return $this->render('@CitadelAureum/core/inventory/index.html.twig', [
            'rows' => $rows,
        ]);
    }

    #[Route('/inventory/reorder', name: 'inventory_reorder')]
    public function reorder(): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createAccessDeniedException();
        }

        $forecasts = $this->forecastService->forecastForHotel($hotel);

        return $this->render('@CitadelAureum/core/inventory/reorder.html.twig', [
            'groups' => self::groupByWeek($forecasts),
            'actionable' => count(array_filter($forecasts, static fn (ItemForecast $f): bool => $f->status->isActionable())),
            'needsReview' => count(array_filter($forecasts, static fn (ItemForecast $f): bool => $f->needsReview)),
            'needsSetup' => count(array_filter($forecasts, static fn (ItemForecast $f): bool => $f->status === ReorderStatus::NEEDS_SETUP)),
        ]);
    }

    /**
     * @param array<ItemForecast> $forecasts
     * @return array<string, array<ItemForecast>>
     */
    public static function groupByWeek(array $forecasts): array
    {
        $dated = [];
        $undated = [];

        foreach ($forecasts as $forecast) {
            $week = $forecast->orderByWeek();
            if ($week === null) {
                $undated[] = $forecast;
                continue;
            }

            $dated[$week][] = $forecast;
        }

        ksort($dated);

        return $undated === [] ? $dated : $dated + ['' => $undated];
    }

    #[Route('/inventory/take/{id}', name: 'inventory_take', requirements: ['id' => '\d+'])]
    #[IsGranted('aureum.module.inventory.count')]
    public function take(int $id): Response
    {
        $hotel = $this->aureumService->getHotel();
        $inventory = $this->inventoryRepository->find($id);
        if ($hotel === null || $inventory === null || $inventory->getHotel()->getId() !== $hotel->getId()) {
            throw $this->createNotFoundException();
        }

        return $this->render('@CitadelAureum/core/inventory/take.html.twig', [
            'inventory' => $inventory,
        ]);
    }

    #[Route('/inventory/items/{id}', name: 'inventory_item', requirements: ['id' => '\d+'])]
    public function item(int $id): Response
    {
        $hotel = $this->aureumService->getHotel();
        $item = $this->itemRepository->find($id);
        if ($hotel === null || $item === null
            || $item->getCategory()->getInventory()->getHotel()->getId() !== $hotel->getId()
        ) {
            throw $this->createNotFoundException();
        }

        $since = new DateTimeImmutable('-' . InventoryForecastService::WINDOW_DAYS . ' days');

        return $this->render('@CitadelAureum/core/inventory/item.html.twig', [
            'item' => $item,
            'forecast' => $this->forecastService->forecastForItem($item),
            'counts' => $this->countLineRepository->findForItemSince($item, $since),
            'movements' => $this->movementRepository->findForItemSince($item, $since),
            'logs' => $this->itemLogRepository->findByItem($item),
        ]);
    }
}
