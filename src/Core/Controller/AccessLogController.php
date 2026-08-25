<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Repository\AccessLogRepository;
use Citadel\Aureum\Core\Security\AureumVoter;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\RetentionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/access-log', name: 'access_log')]
#[IsGranted(AureumVoter::RBAC_MANAGE)]
class AccessLogController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly AccessLogRepository $accessLogRepository,
    ) {
    }

    public function __invoke(): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('@CitadelAureum/core/access_log.html.twig', [
            'entries' => $this->accessLogRepository->findRecentForHotel($hotel),
            'retainedMonths' => RetentionService::ACCESS_LOG_MONTHS,
        ]);
    }
}
