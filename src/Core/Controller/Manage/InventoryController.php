<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller\Manage;

use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Form\InventoryType;
use Citadel\Aureum\Core\Repository\InventoryRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.inventory.manage')]
class InventoryController extends AbstractController
{
    use ManageControllerTrait;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly InventoryRepository $inventoryRepository,
    ) {
    }

    #[Route('/inventory/manage/inventories', name: 'inventory_manage_inventories', methods: ['GET'])]
    public function index(): Response
    {
        $hotel = $this->requireHotel();
        $inventories = $this->inventoryRepository->findByHotel($hotel);

        $inventory = new Inventory();
        $inventory->setHotel($hotel);

        $editForms = [];
        foreach ($inventories as $existing) {
            $editForms[$existing->getId()] = $this->createNamedForm(
                'inventory_' . $existing->getId(),
                InventoryType::class,
                $existing,
                ['action' => $this->generateUrl('aureum_inventory_inventory_save')],
            )->createView();
        }

        return $this->render('@CitadelAureum/core/inventory/manage/inventories.html.twig', [
            'inventories' => $inventories,
            'inventoryForm' => $this->createForm(InventoryType::class, $inventory, [
                'action' => $this->generateUrl('aureum_inventory_inventory_save'),
            ]),
            'inventoryEditForms' => $editForms,
        ]);
    }

    #[Route('/inventory/manage/inventories/save', name: 'inventory_inventory_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $hotel = $this->requireHotel();

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

        return $this->redirectToRoute('aureum_inventory_manage_inventories');
    }
}
