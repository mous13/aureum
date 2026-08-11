<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Enum\BathroomStyle;
use Citadel\Aureum\Core\Entity\Enum\BedSize;
use Citadel\Aureum\Core\Entity\Enum\FeatureType;
use Citadel\Aureum\Core\Entity\Enum\RoomView;
use Citadel\Aureum\Core\Entity\Floor;
use Citadel\Aureum\Core\Entity\FloorFeature;
use Citadel\Aureum\Core\Entity\Room;
use Citadel\Aureum\Core\Form\FloorType;
use Citadel\Aureum\Core\Repository\FloorFeatureRepository;
use Citadel\Aureum\Core\Repository\FloorRepository;
use Citadel\Aureum\Core\Repository\RoomRepository;
use Citadel\Aureum\Core\Repository\RoomTypeRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\FloorPlanValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rooms/floors', name: 'floors_')]
#[IsGranted('aureum.module.rooms.manage')]
class FloorController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly FloorRepository $floorRepository,
        private readonly RoomRepository $roomRepository,
        private readonly FloorFeatureRepository $floorFeatureRepository,
        private readonly RoomTypeRepository $roomTypeRepository,
        private readonly FloorPlanValidator $validator,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();

        $floor = new Floor();
        $form = $this->createForm(FloorType::class, $floor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $floor->setHotel($hotel);
            $this->floorRepository->save($floor);
            $this->addFlash('success', 'Floor created');

            return $this->redirectToRoute('aureum_floors_layout', ['id' => $floor->getId()]);
        }

        return $this->render('@CitadelAureum/core/rooms/floors.html.twig', [
            'floors' => $this->floorRepository->findByHotelOrdered($hotel),
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Floor $floor): Response
    {
        $this->denyUnlessSameHotel($floor);

        $form = $this->createForm(FloorType::class, $floor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->floorRepository->save($floor);
            $this->addFlash('success', 'Floor updated');

            return $this->redirectToRoute('aureum_floors_list');
        }

        return $this->render('@CitadelAureum/core/rooms/floor_form.html.twig', [
            'form' => $form,
            'floor' => $floor,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Floor $floor): Response
    {
        $this->denyUnlessSameHotel($floor);

        $this->floorRepository->remove($floor);
        $this->addFlash('success', 'Floor deleted');

        return $this->redirectToRoute('aureum_floors_list');
    }

    #[Route('/{id}/layout', name: 'layout')]
    public function layout(Floor $floor): Response
    {
        $this->denyUnlessSameHotel($floor);

        $rooms = array_map(static fn (Room $room) => [
            'id' => $room->getId(),
            'number' => $room->getNumber(),
            'roomTypeId' => $room->getRoomType()->getId(),
            'bathroomStyle' => $room->getBathroomStyle()->value,
            'view' => $room->getView()->value,
            'bedSize' => $room->getBedSize()->value,
            'hasStairs' => $room->hasStairs(),
            'interconnecting' => $room->isInterconnecting(),
            'lastLet' => $room->isLastLet(),
            'cells' => $room->getCells(),
        ], $this->roomRepository->findByFloor($floor));

        $features = array_map(static fn (FloorFeature $feature) => [
            'id' => $feature->getId(),
            'type' => $feature->getType()->value,
            'label' => $feature->getLabel(),
            'hasSteps' => $feature->hasSteps(),
            'department' => $feature->getDepartment(),
            'contents' => $feature->getContents(),
            'cells' => $feature->getCells(),
        ], $this->floorFeatureRepository->findByFloor($floor));

        $enumValues = static fn (array $cases) => array_map(static fn ($case) => $case->value, $cases);

        return $this->render('@CitadelAureum/core/rooms/floor_layout.html.twig', [
            'floor' => $floor,
            'rooms' => $rooms,
            'features' => $features,
            'roomTypes' => $this->roomTypeRepository->findSelectableByHotel($floor->getHotel()),
            'options' => [
                'bathroomStyle' => $enumValues(BathroomStyle::cases()),
                'view' => $enumValues(RoomView::cases()),
                'bedSize' => $enumValues(BedSize::cases()),
                'featureType' => $enumValues(FeatureType::cases()),
            ],
        ]);
    }

    #[Route('/{id}/rooms', name: 'room_save', methods: ['POST'])]
    public function saveRoom(Request $request, Floor $floor): JsonResponse
    {
        $this->denyUnlessSameHotel($floor);

        $data = $request->toArray();

        $room = null;
        if (!empty($data['id'])) {
            $room = $this->roomRepository->find((int)$data['id']);
            if ($room === null || $room->getFloor()->getId() !== $floor->getId()) {
                return new JsonResponse(['error' => 'Room not found on this floor'], Response::HTTP_NOT_FOUND);
            }
        }

        $cells = $this->validator->normalizeCells($data['cells'] ?? [], $floor);
        if ($cells === null) {
            return new JsonResponse(['error' => 'Invalid or out-of-bounds cells'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->validator->cellsOverlap($floor, $cells, ignoreRoom: $room)) {
            return new JsonResponse(['error' => 'Cells overlap another room or feature'], Response::HTTP_CONFLICT);
        }

        $roomType = $this->roomTypeRepository->findOneForHotel(
            (int)($data['roomTypeId'] ?? 0),
            $floor->getHotel(),
        );
        if ($roomType === null) {
            return new JsonResponse(['error' => 'Unknown room type'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $room ??= new Room();
            $room->setFloor($floor);
            $room->setNumber(trim((string)($data['number'] ?? '')));
            $room->setRoomType($roomType);
            $room->setBathroomStyle(BathroomStyle::from($data['bathroomStyle'] ?? ''));
            $room->setView(RoomView::from($data['view'] ?? ''));
            $room->setBedSize(BedSize::from($data['bedSize'] ?? ''));
            $room->setHasStairs((bool)($data['hasStairs'] ?? false));
            $room->setInterconnecting((bool)($data['interconnecting'] ?? false));
            $room->setLastLet((bool)($data['lastLet'] ?? false));
            $room->setCells($cells);
        } catch (\ValueError) {
            return new JsonResponse(['error' => 'Invalid attribute value'], Response::HTTP_BAD_REQUEST);
        }

        if ($room->getNumber() === '') {
            return new JsonResponse(['error' => 'Room number is required'], Response::HTTP_BAD_REQUEST);
        }

        $this->roomRepository->save($room);

        return new JsonResponse(['id' => $room->getId()]);
    }

    #[Route('/{floorId}/rooms/{id}', name: 'room_delete', methods: ['DELETE'])]
    public function deleteRoom(int $floorId, Room $room): JsonResponse
    {
        $this->denyUnlessSameHotel($room->getFloor());

        if ($room->getFloor()->getId() !== $floorId) {
            return new JsonResponse(['error' => 'Room not found on this floor'], Response::HTTP_NOT_FOUND);
        }

        $this->roomRepository->remove($room);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/features', name: 'feature_save', methods: ['POST'])]
    public function saveFeature(Request $request, Floor $floor): JsonResponse
    {
        $this->denyUnlessSameHotel($floor);

        $data = $request->toArray();

        $feature = null;
        if (!empty($data['id'])) {
            $feature = $this->floorFeatureRepository->find((int)$data['id']);
            if ($feature === null || $feature->getFloor()->getId() !== $floor->getId()) {
                return new JsonResponse(['error' => 'Feature not found on this floor'], Response::HTTP_NOT_FOUND);
            }
        }

        $cells = $this->validator->normalizeCells($data['cells'] ?? [], $floor);
        if ($cells === null) {
            return new JsonResponse(['error' => 'Invalid or out-of-bounds cells'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->validator->cellsOverlap($floor, $cells, ignoreFeature: $feature)) {
            return new JsonResponse(['error' => 'Cells overlap another room or feature'], Response::HTTP_CONFLICT);
        }

        try {
            $feature ??= new FloorFeature();
            $feature->setFloor($floor);
            $feature->setType(FeatureType::from($data['type'] ?? ''));
            $feature->setLabel($this->nullableText($data['label'] ?? null));
            $feature->setHasSteps((bool)($data['hasSteps'] ?? false));
            $feature->setCells($cells);

            if ($feature->getType()->usesStorageFields()) {
                $feature->setDepartment($this->nullableText($data['department'] ?? null));
                $feature->setContents($this->nullableText($data['contents'] ?? null));
            } else {
                $feature->setDepartment(null);
                $feature->setContents(null);
            }
        } catch (\ValueError) {
            return new JsonResponse(['error' => 'Invalid feature type'], Response::HTTP_BAD_REQUEST);
        }

        $this->floorFeatureRepository->save($feature);

        return new JsonResponse(['id' => $feature->getId()]);
    }

    #[Route('/{floorId}/features/{id}', name: 'feature_delete', methods: ['DELETE'])]
    public function deleteFeature(int $floorId, FloorFeature $feature): JsonResponse
    {
        $this->denyUnlessSameHotel($feature->getFloor());

        if ($feature->getFloor()->getId() !== $floorId) {
            return new JsonResponse(['error' => 'Feature not found on this floor'], Response::HTTP_NOT_FOUND);
        }

        $this->floorFeatureRepository->remove($feature);

        return new JsonResponse(['ok' => true]);
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string)($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function denyUnlessSameHotel(Floor $floor): void
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null || $floor->getHotel()->getId() !== $hotel->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
