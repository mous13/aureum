<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Enum\MovementReason;
use Citadel\Aureum\Core\Entity\Enum\StorageLocationType;
use Citadel\Aureum\Core\Entity\StockMovement;
use Citadel\Aureum\Core\Form\StockMovementType;
use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\StockMovementRepository;
use Citadel\Aureum\Core\Repository\StorageLocationRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.inventory.count')]
class InventoryMovementController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly InventoryItemRepository $itemRepository,
        private readonly StorageLocationRepository $locationRepository,
        private readonly StockMovementRepository $movementRepository,
    ) {
    }

    #[Route('/inventory/movements', name: 'inventory_movements', methods: ['GET'])]
    public function index(): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createAccessDeniedException();
        }

        $movement = new StockMovement();
        $form = $this->createForm(StockMovementType::class, $movement, [
            'action' => $this->generateUrl('aureum_inventory_movement_save'),
            'items' => $this->itemRepository->findActiveByHotel($hotel),
            'locations' => $this->locationRepository->findActiveByHotel($hotel, StorageLocationType::WORKING),
        ]);

        $movements = $this->movementRepository->findRecentByHotel($hotel);

        return $this->render('@CitadelAureum/core/inventory/movement.html.twig', [
            'form' => $form,
            'movements' => $movements,
        ]);
    }

    #[Route('/inventory/movements/record', name: 'inventory_movement_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        $employee = $this->aureumService->getEmployee();
        if ($hotel === null || $employee === null) {
            throw $this->createAccessDeniedException();
        }

        $movement = new StockMovement();
        $form = $this->createForm(StockMovementType::class, $movement, [
            'items' => $this->itemRepository->findActiveByHotel($hotel),
            'locations' => $this->locationRepository->findActiveByHotel($hotel, StorageLocationType::WORKING),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $missingDestination = $movement->getReason() === MovementReason::TRANSFER && $movement->getDestination() === null;

            if ($missingDestination) {
                $form->get('destination')->addError(new FormError('A destination is required for transfers.'));
            } elseif ($movement->getItem()->getCategory()->getInventory()->getHotel()->getId() !== $hotel->getId()) {
                throw $this->createNotFoundException();
            } else {
                $movement->setDirection(StockMovementType::directionFor($movement->getReason(), $movement->getDirection()));
                $movement->setQuantity(abs($movement->getQuantity()));
                $movement->setRecordedBy($employee);
                $movement->setHotel($hotel);

                $this->movementRepository->save($movement);
                $this->addFlash('success', 'Movement recorded.');

                return $this->redirectToRoute('aureum_inventory_movements');
            }
        }

        $this->addFlash('error', 'Movement could not be recorded.');

        return $this->redirectToRoute('aureum_inventory_movements');
    }
}
