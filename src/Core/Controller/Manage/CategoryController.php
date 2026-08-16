<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller\Manage;

use Citadel\Aureum\Core\Entity\InventoryCategory;
use Citadel\Aureum\Core\Form\InventoryCategoryType;
use Citadel\Aureum\Core\Repository\InventoryCategoryRepository;
use Citadel\Aureum\Core\Repository\InventoryRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.inventory.manage')]
class CategoryController extends AbstractController
{
    use ManageControllerTrait;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly InventoryRepository $inventoryRepository,
        private readonly InventoryCategoryRepository $categoryRepository,
    ) {
    }

    #[Route('/inventory/manage/categories', name: 'inventory_manage_categories', methods: ['GET'])]
    public function index(): Response
    {
        $hotel = $this->requireHotel();
        $inventories = $this->inventoryRepository->findByHotel($hotel);

        $editForms = [];
        foreach ($this->categoryRepository->findByHotel($hotel) as $existing) {
            $editForms[$existing->getId()] = $this->createNamedForm(
                'inventory_category_' . $existing->getId(),
                InventoryCategoryType::class,
                $existing,
                [
                    'action' => $this->generateUrl('aureum_inventory_category_save'),
                    'inventories' => $inventories,
                ],
            )->createView();
        }

        return $this->render('@CitadelAureum/core/inventory/manage/categories.html.twig', [
            'inventories' => $inventories,
            'categoryForm' => $this->createForm(InventoryCategoryType::class, new InventoryCategory(), [
                'action' => $this->generateUrl('aureum_inventory_category_save'),
                'inventories' => $inventories,
            ]),
            'categoryEditForms' => $editForms,
        ]);
    }

    #[Route('/inventory/manage/categories/save', name: 'inventory_category_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $hotel = $this->requireHotel();

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

        return $this->redirectToRoute('aureum_inventory_manage_categories');
    }
}
