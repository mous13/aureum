<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Enum\StorageLocationType as StorageLocationTypeEnum;
use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Entity\InventoryCategory;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Citadel\Aureum\Core\Form\InventoryCategoryType;
use Citadel\Aureum\Core\Form\InventoryItemType;
use Citadel\Aureum\Core\Form\InventoryType;
use Citadel\Aureum\Core\Form\StorageLocationType;
use Citadel\Aureum\Core\Repository\InventoryCategoryRepository;
use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\InventoryRepository;
use Citadel\Aureum\Core\Repository\StorageLocationRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\InventoryItemLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.inventory.manage')]
class InventoryManageController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly InventoryRepository $inventoryRepository,
        private readonly InventoryCategoryRepository $categoryRepository,
        private readonly InventoryItemRepository $itemRepository,
        private readonly StorageLocationRepository $locationRepository,
        private readonly InventoryItemLogService $itemLogService,
    ) {
    }

    #[Route('/inventory/manage', name: 'inventory_manage')]
    public function index(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createAccessDeniedException();
        }

        $inventories = $this->inventoryRepository->findByHotel($hotel);
        $categories = $this->categoryRepository->findByHotel($hotel);
        $locations = $this->locationRepository->findActiveByHotel($hotel);
        $itemLocations = $this->locationRepository->findActiveByHotel($hotel, StorageLocationTypeEnum::BULK);

        $location = new StorageLocation();
        $location->setHotel($hotel);

        $locationForm = $this->createForm(StorageLocationType::class, $location, [
            'action' => $this->generateUrl('aureum_inventory_location_save'),
        ]);

        $inventory = new Inventory();
        $inventory->setHotel($hotel);

        $inventoryForm = $this->createForm(InventoryType::class, $inventory, [
            'action' => $this->generateUrl('aureum_inventory_inventory_save'),
        ]);

        $category = new InventoryCategory();

        $categoryForm = $this->createForm(InventoryCategoryType::class, $category, [
            'action' => $this->generateUrl('aureum_inventory_category_save'),
            'inventories' => $inventories,
        ]);

        $item = new InventoryItem();

        $itemForm = $this->createForm(InventoryItemType::class, $item, [
            'action' => $this->generateUrl('aureum_inventory_item_save'),
            'categories' => $categories,
            'locations' => $itemLocations,
        ]);

        $locationEditForms = [];
        foreach ($locations as $existingLocation) {
            $locationEditForms[$existingLocation->getId()] = $this->createNamedForm(
                'storage_location_' . $existingLocation->getId(),
                StorageLocationType::class,
                $existingLocation,
                ['action' => $this->generateUrl('aureum_inventory_location_save')],
            )->createView();
        }

        $inventoryEditForms = [];
        foreach ($inventories as $existingInventory) {
            $inventoryEditForms[$existingInventory->getId()] = $this->createNamedForm(
                'inventory_' . $existingInventory->getId(),
                InventoryType::class,
                $existingInventory,
                ['action' => $this->generateUrl('aureum_inventory_inventory_save')],
            )->createView();
        }

        $categoryEditForms = [];
        $itemEditForms = [];
        foreach ($categories as $existingCategory) {
            $categoryEditForms[$existingCategory->getId()] = $this->createNamedForm(
                'inventory_category_' . $existingCategory->getId(),
                InventoryCategoryType::class,
                $existingCategory,
                [
                    'action' => $this->generateUrl('aureum_inventory_category_save'),
                    'inventories' => $inventories,
                ],
            )->createView();

            foreach ($existingCategory->getItems() as $existingItem) {
                $itemEditForms[$existingItem->getId()] = $this->createNamedForm(
                    'inventory_item_' . $existingItem->getId(),
                    InventoryItemType::class,
                    $existingItem,
                    [
                        'action' => $this->generateUrl('aureum_inventory_item_save'),
                        'categories' => $categories,
                        'locations' => self::locationChoicesWithCurrent($itemLocations, $existingItem->getLocation()),
                    ],
                )->createView();
            }
        }

        return $this->render('@CitadelAureum/core/inventory/manage.html.twig', [
            'inventories' => $inventories,
            'categories' => $categories,
            'locations' => $locations,
            'locationForm' => $locationForm,
            'inventoryForm' => $inventoryForm,
            'categoryForm' => $categoryForm,
            'itemForm' => $itemForm,
            'locationEditForms' => $locationEditForms,
            'inventoryEditForms' => $inventoryEditForms,
            'categoryEditForms' => $categoryEditForms,
            'itemEditForms' => $itemEditForms,
        ]);
    }

    #[Route('/inventory/manage/locations', name: 'inventory_location_save', methods: ['POST'])]
    public function saveLocation(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createAccessDeniedException();
        }

        $id = $request->request->getInt('storage_location_id');
        $location = $id > 0 ? $this->locationRepository->find($id) : new StorageLocation();
        if ($location === null || ($id > 0 && $location->getHotel()->getId() !== $hotel->getId())) {
            throw $this->createNotFoundException();
        }

        $location->setHotel($hotel);

        $formName = $id > 0 ? 'storage_location_' . $id : 'storage_location';
        $form = $this->createNamedForm($formName, StorageLocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->locationRepository->save($location);
            $this->addFlash('success', 'Storage location saved.');
        } else {
            $this->addFlash('error', 'Storage location could not be saved.');
        }

        return $this->redirectToRoute('aureum_inventory_manage');
    }

    #[Route('/inventory/manage/inventories', name: 'inventory_inventory_save', methods: ['POST'])]
    public function saveInventory(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createAccessDeniedException();
        }

        $id = $request->request->getInt('inventory_id');
        $inventory = $id > 0 ? $this->inventoryRepository->find($id) : new Inventory();
        if ($inventory === null || ($id > 0 && $inventory->getHotel()->getId() !== $hotel->getId())) {
            throw $this->createNotFoundException();
        }

        $inventory->setHotel($hotel);

        $formName = $id > 0 ? 'inventory_' . $id : 'inventory';
        $form = $this->createNamedForm($formName, InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->inventoryRepository->save($inventory);
            $this->addFlash('success', 'Inventory saved.');
        } else {
            $this->addFlash('error', 'Inventory could not be saved.');
        }

        return $this->redirectToRoute('aureum_inventory_manage');
    }

    #[Route('/inventory/manage/categories', name: 'inventory_category_save', methods: ['POST'])]
    public function saveCategory(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createAccessDeniedException();
        }

        $id = $request->request->getInt('inventory_category_id');
        $category = $id > 0 ? $this->categoryRepository->find($id) : new InventoryCategory();
        if ($category === null || ($id > 0 && $category->getInventory()->getHotel()->getId() !== $hotel->getId())) {
            throw $this->createNotFoundException();
        }

        $formName = $id > 0 ? 'inventory_category_' . $id : 'inventory_category';
        $form = $this->createNamedForm($formName, InventoryCategoryType::class, $category, [
            'inventories' => $this->inventoryRepository->findByHotel($hotel),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category);
            $this->addFlash('success', 'Category saved.');
        } else {
            $this->addFlash('error', 'Category could not be saved.');
        }

        return $this->redirectToRoute('aureum_inventory_manage');
    }

    #[Route('/inventory/manage/items', name: 'inventory_item_save', methods: ['POST'])]
    public function saveItem(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        $employee = $this->aureumService->getEmployee();
        if ($hotel === null || $employee === null) {
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

        return $this->redirectToRoute('aureum_inventory_manage');
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createNamedForm(string $name, string $type, mixed $data, array $options = []): FormInterface
    {
        return $this->container->get('form.factory')->createNamed($name, $type, $data, $options);
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
