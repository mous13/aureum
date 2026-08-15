<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\StorageLocation;
use Citadel\Aureum\Core\Form\StorageLocationType;
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

        $location = new StorageLocation();
        $location->setHotel($hotel);

        $locationForm = $this->createForm(StorageLocationType::class, $location, [
            'action' => $this->generateUrl('aureum_inventory_location_save'),
        ]);

        return $this->render('@CitadelAureum/core/inventory/manage.html.twig', [
            'inventories' => $this->inventoryRepository->findByHotel($hotel),
            'locations' => $this->locationRepository->findActiveByHotel($hotel),
            'locationForm' => $locationForm,
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
}
