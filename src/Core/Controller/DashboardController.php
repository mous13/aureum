<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Repository\EmployeeRepository;
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
    ){
    }
    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->security->getUser();
        $employee = $this->employeeRepository->findOneBy(['user' => $user->getId()]);


        return $this->render('@CitadelAureum/core/dashboard.html.twig',[
            'employee' => $employee,
        ]);
    }

}
