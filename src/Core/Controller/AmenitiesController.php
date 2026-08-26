<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Repository\AmenityBoardRepository;
use Citadel\Aureum\Core\Service\AmenityBoardService;
use Citadel\Aureum\Core\Service\AureumService;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/amenities', name: 'amenities_')]
#[IsGranted('aureum.module.amenities.view')]
class AmenitiesController extends AbstractController
{
    public function __construct(
        private readonly AmenityBoardRepository $boardRepository,
        private readonly AmenityBoardService $boardService,
        private readonly AureumService $aureumService,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $hotel = $this->aureumService->getHotel();
        $latest = $this->boardRepository->findLatest($hotel);

        return $this->render('@CitadelAureum/core/amenities/amenities.html.twig', [
            'board' => $latest,
            'readOnly' => false,
            'turnoverDue' => $this->boardService->isTurnoverDue($latest, $this->today()),
        ]);
    }

    #[Route('/history', name: 'history')]
    public function history(): Response
    {
        $hotel = $this->aureumService->getHotel();

        return $this->render('@CitadelAureum/core/amenities/history.html.twig', [
            'boards' => $this->boardRepository->findAllByHotel($hotel),
        ]);
    }

    #[Route('/board/{id}', name: 'board', requirements: ['id' => '\d+'])]
    public function board(int $id): Response
    {
        $hotel = $this->aureumService->getHotel();
        $board = $this->boardRepository->findOneBy(['id' => $id, 'hotel' => $hotel]);
        if ($board === null) {
            throw $this->createNotFoundException();
        }

        $latest = $this->boardRepository->findLatest($hotel);

        return $this->render('@CitadelAureum/core/amenities/amenities.html.twig', [
            'board' => $board,
            'readOnly' => $board->getId() !== $latest?->getId(),
            'turnoverDue' => $this->boardService->isTurnoverDue($latest, $this->today()),
        ]);
    }

    #[Route('/start', name: 'start', methods: ['POST'])]
    #[IsGranted('aureum.module.amenities.manage')]
    public function start(Request $request): Response
    {
        $token = (string)$request->request->get('_token');
        if (!$this->isCsrfTokenValid('aureum_amenities_start', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $employee = $this->aureumService->getEmployee();
        $latest = $this->boardRepository->findLatest($employee->getHotel());
        if (!$this->boardService->isTurnoverDue($latest, $this->today())) {
            $this->addFlash('error', 'Today\'s board has already been started.');

            return $this->redirectToRoute('aureum_amenities_index');
        }

        $this->boardService->startBoard($employee->getHotel(), $employee, $this->today());
        $this->addFlash('success', 'New amenities board started.');

        return $this->redirectToRoute('aureum_amenities_index');
    }

    #[Route('/board/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('aureum.module.amenities.manage')]
    public function delete(Request $request, int $id): Response
    {
        $token = (string)$request->request->get('_token');
        if (!$this->isCsrfTokenValid('aureum_amenities_delete_' . $id, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $hotel = $this->aureumService->getHotel();
        $board = $this->boardRepository->findOneBy(['id' => $id, 'hotel' => $hotel]);
        $latest = $this->boardRepository->findLatest($hotel);
        if ($board === null || $board->getId() !== $latest?->getId()) {
            throw $this->createNotFoundException();
        }

        $this->boardRepository->remove($board);
        $this->addFlash('success', 'Board deleted.');

        return $this->redirectToRoute('aureum_amenities_index');
    }

    private function today(): DateTimeImmutable
    {
        $timezone = $this->aureumService->getEmployee()?->getUser()?->getTimezone() ?? 'UTC';

        return new DateTimeImmutable('now', new DateTimeZone($timezone));
    }
}
