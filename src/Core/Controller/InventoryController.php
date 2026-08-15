<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\InventoryRepository;
use Citadel\Aureum\Core\Repository\StockCountRepository;
use Citadel\Aureum\Core\Service\AureumService;
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
        return $this->render('@CitadelAureum/core/inventory/index.html.twig', ['rows' => []]);
    }

    #[Route('/inventory/take/{id}', name: 'inventory_take', requirements: ['id' => '\d+'])]
    public function take(int $id): Response
    {
        return $this->redirectToRoute('aureum_inventory');
    }
}
