<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller\Manage;

use Citadel\Aureum\Core\Entity\StorageLocation;
use Citadel\Aureum\Core\Form\StorageLocationType;
use Citadel\Aureum\Core\Repository\StorageLocationRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.inventory.manage')]
class StorageLocationController extends AbstractController
{
    use ManageControllerTrait;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly StorageLocationRepository $locationRepository,
    ) {
    }

    #[Route('/inventory/manage/storage-locations', name: 'inventory_manage_locations', methods: ['GET'])]
    public function index(): Response
    {
        $hotel = $this->requireHotel();
        $locations = $this->locationRepository->findActiveByHotel($hotel);

        $location = new StorageLocation();
        $location->setHotel($hotel);

        $editForms = [];
        foreach ($locations as $existing) {
            $editForms[$existing->getId()] = $this->createNamedForm(
                'storage_location_' . $existing->getId(),
                StorageLocationType::class,
                $existing,
                ['action' => $this->generateUrl('aureum_inventory_location_save')],
            )->createView();
        }

        return $this->render('@CitadelAureum/core/inventory/manage/storage_locations.html.twig', [
            'locations' => $locations,
            'locationForm' => $this->createForm(StorageLocationType::class, $location, [
                'action' => $this->generateUrl('aureum_inventory_location_save'),
            ]),
            'locationEditForms' => $editForms,
        ]);
    }

    #[Route('/inventory/manage/storage-locations/save', name: 'inventory_location_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $hotel = $this->requireHotel();

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

        return $this->redirectToRoute('aureum_inventory_manage_locations');
    }
}
