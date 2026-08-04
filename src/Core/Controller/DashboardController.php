<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\DashboardService;
use Forumify\Cms\Controller\Frontend\PageController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly DashboardService $dashboardService,
    ) {
    }

    #[Route('/', name: 'dashboard', priority: 100)]
    public function index(): Response
    {
        if (!$this->aureumService->isEmployee()) {
            return $this->forward(PageController::class, ['urlKey' => '']);
        }

        $employee = $this->aureumService->getEmployee();

        return $this->render('@CitadelAureum/core/dashboard.html.twig', [
            'employee' => $employee,
            ...$this->dashboardService->getDashboardData($employee->getHotel()),
        ]);
    }
}
