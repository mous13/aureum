<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rooms', name: 'rooms_')]
#[IsGranted('aureum.core.rooms.view')]
class RoomsController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
    ) {
    }

    #[Route('', name: 'directory')]
    public function directory(): Response
    {
        $hotel = $this->aureumService->getHotel();

        return $this->render('@CitadelAureum/core/rooms/directory.html.twig', [
            'hotelId' => $hotel->getId(),
        ]);
    }
}
