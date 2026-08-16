<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller\Manage;

use Citadel\Aureum\Core\Entity\Enum\StorageLocationType as StorageLocationTypeEnum;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Citadel\Aureum\Core\Form\InventoryItemType;
use Citadel\Aureum\Core\Repository\InventoryCategoryRepository;
use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\StorageLocationRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\InventoryItemLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.inventory.manage')]
class ItemController extends AbstractController
{
    use ManageControllerTrait;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly InventoryCategoryRepository $categoryRepository,
        private readonly InventoryItemRepository $itemRepository,
        private readonly StorageLocationRepository $locationRepository,
        private readonly InventoryItemLogService $itemLogService,
    ) {
    }

    #[Route('/inventory/manage/items', name: 'inventory_manage_items', methods: ['GET'])]
    public function index(): Response
    {
        $hotel = $this->requireHotel();
        $categories = $this->categoryRepository->findByHotel($hotel);
        $bulkLocations = $this->locationRepository->findActiveByHotel($hotel, StorageLocationTypeEnum::BULK);

        $editForms = [];
        foreach ($categories as $category) {
            foreach ($category->getItems() as $existing) {
                $editForms[$existing->getId()] = $this->createNamedForm(
                    'inventory_item_' . $existing->getId(),
                    InventoryItemType::class,
                    $existing,
                    [
                        'action' => $this->generateUrl('aureum_inventory_item_save'),
                        'categories' => $categories,
                        'locations' => self::locationChoicesWithCurrent($bulkLocations, $existing->getLocation()),
                    ],
                )->createView();
            }
        }

        return $this->render('@CitadelAureum/core/inventory/manage/items.html.twig', [
            'categories' => $categories,
            'itemForm' => $this->createForm(InventoryItemType::class, new InventoryItem(), [
                'action' => $this->generateUrl('aureum_inventory_item_save'),
                'categories' => $categories,
                'locations' => $bulkLocations,
            ]),
            'itemEditForms' => $editForms,
        ]);
    }

    #[Route('/inventory/manage/items/save', name: 'inventory_item_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $hotel = $this->requireHotel();
        $employee = $this->aureumService->getEmployee();
        if ($employee === null) {
            throw $this->createAccessDeniedException();
        }

        $id = $request->request->getInt('inventory_item_id');
        $item = $id > 0 ? $this->itemRepository->find($id) : new InventoryItem();
        if ($item === null) {
            throw $this->createNotFoundException();
        }

        $isNew = $id <= 0;
        $originalData = $isNew ? [] : $this->itemLogService->captureCurrentState($item);

        if (!$isNew && $item->getCategory()->getInventory()->getHotel()->getId() !== $hotel->getId()) {
            throw $this->createNotFoundException();
        }

        $itemLocations = $this->locationRepository->findActiveByHotel($hotel, StorageLocationTypeEnum::BULK);
        if (!$isNew) {
            $itemLocations = self::locationChoicesWithCurrent($itemLocations, $item->getLocation());
        }

        $formName = $isNew ? 'inventory_item' : 'inventory_item_' . $id;
        $form = $this->createNamedForm($formName, InventoryItemType::class, $item, [
            'categories' => $this->categoryRepository->findByHotel($hotel),
            'locations' => $itemLocations,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->itemRepository->save($item);

            if ($isNew) {
                $this->itemLogService->logCreated($item, $employee);
            } else {
                $this->itemLogService->logUpdated($item, $originalData, $employee);
            }

            $this->addFlash('success', 'Item saved.');
        } else {
            $this->addFlash('error', 'Item could not be saved.');
        }

        return $this->redirectToRoute('aureum_inventory_manage_items');
    }

    /**
     * @param array<StorageLocation> $locations
     * @return array<StorageLocation>
     */
    public static function locationChoicesWithCurrent(array $locations, StorageLocation $current): array
    {
        foreach ($locations as $location) {
            if ($location->getId() === $current->getId()) {
                return $locations;
            }
        }

        return [...$locations, $current];
    }
}
