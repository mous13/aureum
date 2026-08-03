<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events', name: 'events_')]
#[IsGranted('aureum.module.events.view')]
class EventController extends AbstractController
{
    #[Route('', name:'calendar')]
    public function index(Request $request): Response
    {
        return $this->render('@CitadelAureum/core/events/calendar.html.twig', [
            'week' => $request->query->getString('week') ?: null,
        ]);
    }
}
