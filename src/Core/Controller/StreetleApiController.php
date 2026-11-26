<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Controller\Trait\StreetleAccessTrait;
use Citadel\Aureum\Core\Entity\StreetleMap;
use Citadel\Aureum\Core\Entity\StreetleSession;
use Citadel\Aureum\Core\Entity\StreetleStreet;
use Citadel\Aureum\Core\Repository\StreetleMapRepository;
use Citadel\Aureum\Core\Repository\StreetleSessionRepository;
use Citadel\Aureum\Core\Repository\StreetleStreetRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\Streetle\StreetleGame;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/streetle', name: 'streetle_api_')]
#[IsGranted('aureum.module.streetle.view')]
class StreetleApiController extends AbstractController
{
    use StreetleAccessTrait;

    public const CSRF_TOKEN_ID = StreetleController::CSRF_TOKEN_ID;
    private const MAX_GUESS_LENGTH = 120;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly StreetleMapRepository $mapRepository,
        private readonly StreetleStreetRepository $streetRepository,
        private readonly StreetleSessionRepository $sessionRepository,
        private readonly StreetleGame $game,
    ) {
    }

    #[Route('/map', name: 'map', methods: ['GET'])]
    public function map(Request $request): Response
    {
        $map = $this->hotelMap();

        $response = new Response();
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');
        $response->setEtag(hash('xxh128', $map->getId() . '@' . $map->getImportedAt()->format('U.u')));
        $response->setVary('Accept-Encoding');
        if ($response->isNotModified($request)) {
            return $response;
        }

        $body = json_encode([
            'bounds' => $map->getBounds()->toArray(),
            'streets' => $this->streetRepository->findShapes($map),
            'basemap' => $map->getBasemap(),
        ], JSON_THROW_ON_ERROR);

        if (in_array('gzip', $request->getEncodings(), true)) {
            $body = (string)gzencode($body, 6);
            $response->headers->set('Content-Encoding', 'gzip');
        }

        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($body);

        return $response;
    }

    #[Route('/session/{id}/guess', name: 'guess', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function guess(Request $request, int $id): JsonResponse
    {
        [$session, $ended] = $this->openSession($request, $id);
        if ($ended !== null) {
            return $ended;
        }

        $guess = trim((string)($request->toArray()['guess'] ?? ''));
        if ($guess === '' || mb_strlen($guess) > self::MAX_GUESS_LENGTH) {
            throw new UnprocessableEntityHttpException('Type a street name to guess.');
        }

        $outcome = $this->game->guess($session, $this->target($request, $session), $guess, new DateTimeImmutable());

        return new JsonResponse([
            'correct' => $outcome->correct,
            'name' => $outcome->name,
            'guessedName' => $outcome->guessedName,
            'distance' => $outcome->distanceMetres,
            'stats' => $this->stats($session),
        ]);
    }

    #[Route('/session/{id}/hint', name: 'hint', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function hint(Request $request, int $id): JsonResponse
    {
        [$session, $ended] = $this->openSession($request, $id);
        if ($ended !== null) {
            return $ended;
        }

        $level = (int)($request->toArray()['level'] ?? 1);

        return new JsonResponse(['hint' => $this->game->hint($this->target($request, $session), $level)]);
    }

    #[Route('/session/{id}/reveal', name: 'reveal', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reveal(Request $request, int $id): JsonResponse
    {
        [$session, $ended] = $this->openSession($request, $id);
        if ($ended !== null) {
            return $ended;
        }

        $street = $this->target($request, $session);
        $this->game->reveal($session, $street, new DateTimeImmutable());

        return new JsonResponse(['name' => $street->getName(), 'stats' => $this->stats($session)]);
    }

    #[Route('/session/{id}/end', name: 'end', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function end(Request $request, int $id): JsonResponse
    {
        $this->denyUnlessValidCsrf((string)$request->headers->get('X-CSRF-Token'));

        $session = $this->findSession($id);
        $this->game->end($session, new DateTimeImmutable());

        return $this->endedResponse($session);
    }

    /**
     * @return array{StreetleSession, ?JsonResponse}
     */
    private function openSession(Request $request, int $id): array
    {
        $this->denyUnlessValidCsrf((string)$request->headers->get('X-CSRF-Token'));

        $session = $this->findSession($id);
        $this->game->closeIfExpired($session, new DateTimeImmutable());

        return [$session, $session->isOpen() ? null : $this->endedResponse($session)];
    }

    private function target(Request $request, StreetleSession $session): StreetleStreet
    {
        $street = $this->streetRepository->findInMap($this->hotelMap(), (int)($request->toArray()['street'] ?? 0))
            ?? throw $this->createNotFoundException('That street is no longer on the map.');

        if ($session->isSettled($street->getId())) {
            throw new ConflictHttpException('You have already done this street.');
        }

        return $street;
    }

    private function hotelMap(): StreetleMap
    {
        return $this->mapRepository->findOneByHotel($this->employee()->getHotel()) ?? throw $this->createNotFoundException();
    }

    /** @return array{named: int, guesses: int, revealed: int} */
    private function stats(StreetleSession $session): array
    {
        return [
            'named' => $session->getCorrectCount(),
            'guesses' => $session->getGuessCount(),
            'revealed' => count($session->getRevealedStreetIds()),
        ];
    }

    private function endedResponse(StreetleSession $session): JsonResponse
    {
        return new JsonResponse([
            'ended' => true,
            'summaryUrl' => $this->generateUrl('aureum_streetle_summary', ['id' => $session->getId()]),
        ]);
    }
}
