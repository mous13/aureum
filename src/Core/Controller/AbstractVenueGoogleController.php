<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Entity\VenueInterface;
use Citadel\Aureum\Core\Exception\GooglePlacesException;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\VenueGoogleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractVenueGoogleController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly VenueGoogleService $googleService,
        private readonly GooglePlacesKeyManager $keyManager,
    ) {
    }

    abstract protected function getCsrfTokenId(): string;

    protected function handleSearch(Request $request, VenueInterface $venue): Response
    {
        $this->assertGoogleAvailable($request, $venue);

        try {
            return new JsonResponse($this->googleService->search($venue));
        } catch (GooglePlacesException) {
            return new JsonResponse(['error' => 'Google search failed.'], Response::HTTP_BAD_GATEWAY);
        }
    }

    protected function handleLink(Request $request, VenueInterface $venue): Response
    {
        $this->assertGoogleAvailable($request, $venue);

        $placeId = (string)($this->decodeBody($request)['placeId'] ?? '');
        if ($placeId === '' || strlen($placeId) > 255 || preg_match('/^[A-Za-z0-9_-]+$/', $placeId) !== 1) {
            return new JsonResponse(['error' => 'Invalid place.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $times = $this->googleService->link($venue, $placeId);
        } catch (GooglePlacesException) {
            return new JsonResponse(['error' => 'Google sync failed.'], Response::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse(['openingTimes' => $times]);
    }

    protected function handleUnlink(Request $request, VenueInterface $venue): Response
    {
        $this->assertTenant($venue);
        $this->assertCsrf($request);
        $this->googleService->unlink($venue);

        return new JsonResponse(['ok' => true]);
    }

    private function assertGoogleAvailable(Request $request, VenueInterface $venue): void
    {
        $this->assertTenant($venue);
        $this->assertCsrf($request);

        if (!$venue->getHotel()->isGooglePlacesEnabled() || !$this->keyManager->hasKey()) {
            throw $this->createAccessDeniedException('Google Places is not enabled for this hotel.');
        }
    }

    private function assertTenant(VenueInterface $venue): void
    {
        if ($venue->getHotel()->getId() !== $this->aureumService->getHotel()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function assertCsrf(Request $request): void
    {
        $token = (string)($this->decodeBody($request)['_token'] ?? '');
        if (!$this->isCsrfTokenValid($this->getCsrfTokenId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    private function decodeBody(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (JsonException) {
            return [];
        }
    }
}
