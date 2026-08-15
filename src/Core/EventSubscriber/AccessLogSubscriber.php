<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\EventSubscriber;

use Citadel\Aureum\Core\Entity\AccessLog;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Repository\AccessLogRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Records who looked at the modules holding guest contact details.
 *
 * Without this there is no way to answer a hotel asking which of its staff
 * viewed a particular guest's details, and no way to scope who saw what after
 * an incident. Only the modules carrying guest personal data are covered, and
 * only the route and actor are stored - never the records returned.
 *
 * Runs on kernel.terminate so a slow write cannot delay the response.
 */
#[AsEventListener(event: KernelEvents::TERMINATE)]
final class AccessLogSubscriber
{
    /**
     * Route name prefix to the module it belongs to. Longest prefixes first so
     * aureum_lost_property is not swallowed by a shorter match.
     */
    private const ROUTE_MODULES = [
        'aureum_lost_property' => Module::LOST_PROPERTY,
        'aureum_transfers' => Module::TRANSFERS,
        'aureum_packages' => Module::PACKAGES,
        'aureum_fines' => Module::FINES,
    ];

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly AccessLogRepository $accessLogRepository,
    ) {
    }

    public function __invoke(TerminateEvent $event): void
    {
        if (!$event->getResponse()->isSuccessful() && !$event->getResponse()->isRedirection()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string)$request->attributes->get('_route');

        $module = $this->moduleForRoute($route);
        if ($module === null) {
            return;
        }

        $employee = $this->aureumService->getEmployee();
        if ($employee === null) {
            return;
        }

        $log = new AccessLog();
        $log->setHotel($employee->getHotel());
        $log->setEmployee($employee);
        $log->setEmployeeName($employee->getName());
        $log->setModule($module);
        $log->setMethod($request->getMethod());
        $log->setPath(substr($request->getPathInfo(), 0, 255));

        $this->accessLogRepository->save($log);
    }

    private function moduleForRoute(string $route): ?Module
    {
        foreach (self::ROUTE_MODULES as $prefix => $module) {
            if ($route === $prefix || str_starts_with($route, $prefix . '_')) {
                return $module;
            }
        }

        return null;
    }
}
