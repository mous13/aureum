<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\EventListener;

use Citadel\Aureum\Core\Controller\DashboardController;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;


class IndexListener implements EventSubscriberInterface
{
    public function __construct(
        private Security            $security,
        private AureumService       $aureumService,
        private DashboardController $dashboardController,
    ){
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 10],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if ($route === 'index' && $this->aureumService->isEmployee() !== false) {
            $event->setController([$this->dashboardController, 'index']);
        }
    }
}
