<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Entity\InventoryCategory;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Citadel\Aureum\Core\Form\InventoryCategoryType;
use Citadel\Aureum\Core\Form\InventoryType;
use Citadel\Aureum\Core\Form\StorageLocationType;
use Citadel\Aureum\Core\Repository\InventoryCategoryRepository;
use Citadel\Aureum\Core\Repository\InventoryRepository;
use Citadel\Aureum\Core\Repository\StorageLocationRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        private readonly StorageLocationRepository $locationRepository,
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

        return $this->render('@CitadelAureum/core/inventory/manage.html.twig', [
            'inventories' => $inventories,
            'locations' => $this->locationRepository->findActiveByHotel($hotel),
            'locationForm' => $locationForm,
            'inventoryForm' => $inventoryForm,
            'categoryForm' => $categoryForm,
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

        $form = $this->createForm(StorageLocationType::class, $location);
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

        $form = $this->createForm(InventoryType::class, $inventory);
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

        $category = new InventoryCategory();
        $form = $this->createForm(InventoryCategoryType::class, $category, [
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
}
