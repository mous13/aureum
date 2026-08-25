<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Security;

use Citadel\Aureum\Core\Entity\HotelOwnedInterface;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::CONTROLLER_ARGUMENTS, priority: 10)]
final class HotelTenantSubscriber
{
    private const ADMIN_ROUTE_PREFIX = 'aureum_admin_';

    public function __construct(
        private readonly AureumService $aureumService,
    ) {
    }

    public function __invoke(ControllerArgumentsEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $subjects = array_filter(
            $event->getArguments(),
            static fn (mixed $argument): bool => $argument instanceof HotelOwnedInterface,
        );

        if ($subjects === []) {
            return;
        }

        $route = (string)$event->getRequest()->attributes->get('_route');
        if (str_starts_with($route, self::ADMIN_ROUTE_PREFIX)) {
            return;
        }

        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw new NotFoundHttpException();
        }

        foreach ($subjects as $subject) {
            try {
                $subjectHotel = $subject->getHotel();
            } catch (\Error) {
                throw new NotFoundHttpException();
            }

            if ($subjectHotel === null || $subjectHotel->getId() !== $hotel->getId()) {
                throw new NotFoundHttpException();
            }
        }
    }
}
