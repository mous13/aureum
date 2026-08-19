<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Security;

use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 4)]
final class ForcePasswordChangeSubscriber
{
    private const ALLOWED_ROUTES = [
        'aureum_first_login_password',
        'forumify_core_logout',
        'forumify_core_login',
    ];

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string)$request->attributes->get('_route');

        if ($route === '' || str_starts_with($route, '_')) {
            return;
        }

        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $employee = $this->aureumService->getEmployee();
        if ($employee === null || !$employee->mustChangePassword()) {
            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('aureum_first_login_password')
        ));
    }
}
