<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Form\RestaurantType;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Citadel\Aureum\Core\Repository\RestaurantLogRepository;
use Citadel\Aureum\Core\Repository\RestaurantRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\RestaurantLogService;
use Citadel\Aureum\Core\Service\VotingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[Route('/restaurants', name: 'restaurants_')]
#[IsGranted('aureum.module.restaurants.view')]
class RestaurantsController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly RestaurantRepository $restaurantRepository,
        private readonly HotelRepository $hotelRepository,
        private readonly VotingService $votingService,
        private readonly RestaurantLogService $logService,
        private readonly RestaurantLogRepository $restaurantLogRepository,
    ) {
    }

    #[Route('/list', name: 'list')]
    public function list(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        $employee = $this->aureumService->getEmployee();

        $restaurant = new Restaurant();
        $form = $this->createForm(RestaurantType::class, $restaurant, ['hotel' => $hotel]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('aureum.module.restaurants.manage');
            $restaurant = $form->getData();
            $restaurant->setHotel($hotel);
            $this->restaurantRepository->save($restaurant);
            $this->logService->logCreated($restaurant, $employee);

            return $this->redirectToRoute('aureum_restaurants_list');
        }

        return $this->render('@CitadelAureum/core/restaurants/restaurants.html.twig', [
            'hotelId' => $hotel->getId(),
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    #[IsGranted('aureum.module.restaurants.manage')]
    public function edit(Request $request, Restaurant $restaurant): Response
    {
        $originalData = $this->logService->captureCurrentState($restaurant);
        $employee = $this->aureumService->getEmployee();
        $hotel = $employee->getHotel();

        $form = $this->createForm(RestaurantType::class, $restaurant, ['hotel' => $hotel]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $restaurant->setUpdatedAt(new \DateTime());

            $this->restaurantRepository->save($restaurant);
            $this->logService->logUpdated($restaurant, $originalData, $employee);

            $this->addFlash('success', 'Restaurant Updated');
        }
        return $this->redirectToRoute('aureum_restaurants_list');
    }

    #[Route('/{id}/upvote', 'upvote', methods: ['POST'])]
    public function upvote(Request $request, Restaurant $restaurant): Response
    {
        return $this->handleVote($request, $restaurant, 'up');
    }

    #[Route('/{id}/downvote', 'downvote', methods: ['POST'])]
    public function downvote(Request $request, Restaurant $restaurant): Response
    {
        return $this->handleVote($request, $restaurant, 'down');
    }

    private function handleVote(Request $request, Restaurant $restaurant, string $direction): Response
    {
        $token = (string)$request->request->get('_token');
        if (!$this->isCsrfTokenValid('aureum_restaurant_vote', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $employee = $this->aureumService->getEmployee();
        $this->votingService->vote($restaurant, $employee, $direction, $this->restaurantRepository);

        $referer = (string)$request->headers->get('referer');
        if (str_starts_with($referer, $request->getSchemeAndHttpHost() . '/')
            || preg_match('#^/(?![/\\\\])#', $referer) === 1
        ) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('aureum_restaurants_list');
    }
}
