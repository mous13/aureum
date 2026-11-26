<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Controller\Trait\StreetleAccessTrait;
use Citadel\Aureum\Core\Entity\Enum\StreetleMode;
use Citadel\Aureum\Core\Exception\StreetleException;
use Citadel\Aureum\Core\Repository\StreetleMapRepository;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;
use Citadel\Aureum\Core\Repository\StreetleStreetRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\Streetle\StreetleBounds;
use Citadel\Aureum\Core\Service\Streetle\StreetleGame;
use Citadel\Aureum\Core\Service\Streetle\StreetleLeaderboard;
use Citadel\Aureum\Core\Service\Streetle\StreetleMapImporter;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/streetle', name: 'streetle_')]
#[IsGranted('aureum.module.streetle.view')]
class StreetleController extends AbstractController
{
    use StreetleAccessTrait;

    public const CSRF_TOKEN_ID = 'aureum_streetle';
    private const IMPORT_TIME_LIMIT_SECONDS = 300;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly StreetleMapRepository $mapRepository,
        private readonly StreetleStreetRepository $streetRepository,
        private readonly StreetleSessionRepository $sessionRepository,
        private readonly StreetleGame $game,
        private readonly StreetleLeaderboard $leaderboard,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $employee = $this->employee();
        $boards = [];
        foreach (StreetleMode::cases() as $mode) {
            $entries = $this->leaderboard->forHotel($employee->getHotel(), $mode);
            $boards[] = [
                'mode' => $mode,
                'entries' => $entries,
                'mine' => StreetleLeaderboard::entryFor($entries, $employee->getId()),
                'rival' => StreetleLeaderboard::rivalFor($entries, $employee->getId()),
            ];
        }

        return $this->render('@CitadelAureum/core/streetle/index.html.twig', [
            'map' => $this->mapRepository->findOneByHotel($employee->getHotel()),
            'boards' => $boards,
            'employee' => $employee,
        ]);
    }

    #[Route('/start', name: 'start', methods: ['POST'])]
    public function start(Request $request): Response
    {
        $this->denyUnlessValidCsrf((string)$request->request->get('_token'));

        $mode = StreetleMode::tryFrom((string)$request->request->get('mode'));
        $employee = $this->employee();
        if ($mode === null || $this->mapRepository->findOneByHotel($employee->getHotel()) === null) {
            return $this->redirectToRoute('aureum_streetle_index');
        }

        $session = $this->game->start($employee, $mode, new DateTimeImmutable());

        return $this->redirectToRoute('aureum_streetle_play', ['id' => $session->getId()]);
    }

    #[Route('/session/{id}', name: 'play', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function play(int $id): Response
    {
        $session = $this->findSession($id);
        $map = $this->mapRepository->findOneByHotel($session->getHotel());
        if ($map === null || $this->game->closeIfExpired($session, new DateTimeImmutable()) || !$session->isOpen()) {
            return $this->redirectToRoute('aureum_streetle_summary', ['id' => $session->getId()]);
        }

        return $this->render('@CitadelAureum/core/streetle/play.html.twig', [
            'session' => $session,
            'map' => $map,
            'named' => $this->streetRepository->findLabels($map, $session->getNamedStreetIds()),
            'revealed' => $this->streetRepository->findLabels($map, $session->getRevealedStreetIds()),
            'maxHintLevel' => StreetleGame::MAX_HINT_LEVEL,
        ]);
    }

    #[Route('/session/{id}/summary', name: 'summary', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function summary(int $id): Response
    {
        $session = $this->findSession($id);
        if ($session->isOpen()) {
            return $this->redirectToRoute('aureum_streetle_play', ['id' => $session->getId()]);
        }

        $employee = $session->getEmployee();
        $entries = $this->leaderboard->forHotel($session->getHotel(), $session->getMode());
        $mine = StreetleLeaderboard::entryFor($entries, $employee->getId());

        return $this->render('@CitadelAureum/core/streetle/summary.html.twig', [
            'session' => $session,
            'entries' => $entries,
            'mine' => $mine,
            'rival' => StreetleLeaderboard::rivalFor($entries, $employee->getId()),
            'isPersonalBest' => $mine !== null && $mine->session->getId() === $session->getId(),
            'employee' => $employee,
        ]);
    }

    #[Route('/map/refresh', name: 'refresh_map', methods: ['POST'])]
    #[IsGranted('aureum.module.streetle.manage')]
    public function refreshMap(Request $request, StreetleMapImporter $importer): Response
    {
        $this->denyUnlessValidCsrf((string)$request->request->get('_token'));

        set_time_limit(self::IMPORT_TIME_LIMIT_SECONDS);
        try {
            $map = $importer->import($this->employee()->getHotel(), StreetleBounds::manchesterCityCentre());
            $this->addFlash('success', "Map downloaded with {$map->getStreetCount()} streets.");
        } catch (StreetleException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('aureum_streetle_index');
    }
}
