<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig');
    }
}
