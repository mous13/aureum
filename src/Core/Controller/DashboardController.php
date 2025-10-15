<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Forumify\Cms\Controller\Frontend\PageController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DashboardController extends AbstractController
{
    public function __construct(
        readonly Security $security,
        readonly EmployeeRepository $employeeRepository,
        readonly HttpClientInterface $client,
        private readonly AureumService $aureumService,
    ){
    }

    #[Route('/', name: 'dashboard', priority: 100)]
    public function index(): Response
    {
        if(!$this->aureumService->isEmployee()){
            return $this->forward(PageController::class, ['urlKey' => '']);
        }

        return $this->render('@CitadelAureum/core/dashboard.html.twig',[
            'employee' => $this->aureumService->getEmployee(),
        ]);
    }
}