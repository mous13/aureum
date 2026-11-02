<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Restaurant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/restaurants/{id}/google', name: 'restaurants_google_')]
#[IsGranted('aureum.module.restaurants.manage')]
class RestaurantGoogleController extends AbstractVenueGoogleController
{
    public const CSRF_TOKEN_ID = 'aureum_restaurant_google';

    #[Route('/search', name: 'search', methods: ['POST'])]
    public function search(Request $request, Restaurant $restaurant): Response
    {
        return $this->handleSearch($request, $restaurant);
    }

    #[Route('/link', name: 'link', methods: ['POST'])]
    public function link(Request $request, Restaurant $restaurant): Response
    {
        return $this->handleLink($request, $restaurant);
    }

    #[Route('/unlink', name: 'unlink', methods: ['POST'])]
    public function unlink(Request $request, Restaurant $restaurant): Response
    {
        return $this->handleUnlink($request, $restaurant);
    }

    protected function getCsrfTokenId(): string
    {
        return self::CSRF_TOKEN_ID;
    }
}
