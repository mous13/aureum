<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Exception\GooglePlacesException;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\RestaurantGoogleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/restaurants/{id}/google', name: 'restaurants_google_')]
#[IsGranted('aureum.module.restaurants.manage')]
class RestaurantGoogleController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly RestaurantGoogleService $googleService,
        private readonly GooglePlacesKeyManager $keyManager,
    ) {
    }

    #[Route('/search', name: 'search', methods: ['POST'])]
    public function search(Request $request, Restaurant $restaurant): Response
    {
        $this->assertGoogleAvailable($request, $restaurant);

        try {
            return new JsonResponse($this->googleService->search($restaurant));
        } catch (GooglePlacesException) {
            return new JsonResponse(['error' => 'Google search failed.'], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/link', name: 'link', methods: ['POST'])]
    public function link(Request $request, Restaurant $restaurant): Response
    {
        $this->assertGoogleAvailable($request, $restaurant);

        $placeId = (string)($request->toArray()['placeId'] ?? '');
        if ($placeId === '' || strlen($placeId) > 255) {
            return new JsonResponse(['error' => 'Invalid place.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $times = $this->googleService->link($restaurant, $placeId);
        } catch (GooglePlacesException) {
            return new JsonResponse(['error' => 'Google sync failed.'], Response::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse(['openingTimes' => $times]);
    }

    #[Route('/unlink', name: 'unlink', methods: ['POST'])]
    public function unlink(Request $request, Restaurant $restaurant): Response
    {
        $this->assertTenant($restaurant);
        $this->assertCsrf($request);
        $this->googleService->unlink($restaurant);

        return new JsonResponse(['ok' => true]);
    }

    private function assertGoogleAvailable(Request $request, Restaurant $restaurant): void
    {
        $this->assertTenant($restaurant);
        $this->assertCsrf($request);

        if (!$restaurant->getHotel()->isGooglePlacesEnabled() || !$this->keyManager->hasKey()) {
            throw $this->createAccessDeniedException('Google Places is not enabled for this hotel.');
        }
    }

    private function assertTenant(Restaurant $restaurant): void
    {
        if ($restaurant->getHotel()->getId() !== $this->aureumService->getHotel()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function assertCsrf(Request $request): void
    {
        $token = (string)($request->toArray()['_token'] ?? '');
        if (!$this->isCsrfTokenValid('aureum_restaurant_google', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
