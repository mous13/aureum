<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\EventSubscriber;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Service\AureumService;
use Doctrine\DBAL\Connection;
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
        'aureum_bookings' => Module::BOOKINGS,
        'aureum_packages' => Module::PACKAGES,
        'aureum_fines' => Module::FINES,
    ];

    /**
     * LiveComponent AJAX requests all run under the ux_live_component route, so
     * the components rendering guest data are mapped by component name.
     */
    private const COMPONENT_MODULES = [
        'Aureum\\BookingTable' => Module::BOOKINGS,
    ];

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(TerminateEvent $event): void
    {
        if (!$event->getResponse()->isSuccessful() && !$event->getResponse()->isRedirection()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string)$request->attributes->get('_route');

        $module = $route === 'ux_live_component'
            ? self::COMPONENT_MODULES[(string)$request->attributes->get('_live_component')] ?? null
            : $this->moduleForRoute($route);
        if ($module === null) {
            return;
        }

        $employee = $this->aureumService->getEmployee();
        if ($employee === null) {
            return;
        }

        $this->connection->insert('aureum_logs_access', [
            'hotel_id' => $employee->getHotel()->getId(),
            'employee_id' => $employee->getId(),
            'employee_name' => mb_substr($employee->getName(), 0, 100),
            'module' => $module->value,
            'method' => mb_substr($request->getMethod(), 0, 20),
            'path' => mb_substr($request->getPathInfo(), 0, 255),
            'accessed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
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
