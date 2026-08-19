<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Enum\BookingStatus;
use Citadel\Aureum\Core\Entity\Enum\FineStatus;
use Citadel\Aureum\Core\Entity\Enum\LostPropertyStatus;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Enum\PackageStatus;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Repository\AbstractLogRepository;
use Citadel\Aureum\Core\Repository\AnnouncementRepository;
use Citadel\Aureum\Core\Repository\BookingLogRepository;
use Citadel\Aureum\Core\Repository\BookingRepository;
use Citadel\Aureum\Core\Repository\EventRepository;
use Citadel\Aureum\Core\Repository\FineLogRepository;
use Citadel\Aureum\Core\Repository\FineRepository;
use Citadel\Aureum\Core\Repository\LostPropertyLogRepository;
use Citadel\Aureum\Core\Repository\LostPropertyRepository;
use Citadel\Aureum\Core\Repository\PackageLogRepository;
use Citadel\Aureum\Core\Repository\PackageRepository;
use Citadel\Aureum\Core\Repository\RestaurantLogRepository;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardService
{
    private const ACTIVITY_LIMIT = 10;

    public function __construct(
        private readonly Security $security,
        private readonly PackageRepository $packageRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly FineRepository $fineRepository,
        private readonly LostPropertyRepository $lostPropertyRepository,
        private readonly PackageLogRepository $packageLogRepository,
        private readonly BookingLogRepository $bookingLogRepository,
        private readonly FineLogRepository $fineLogRepository,
        private readonly LostPropertyLogRepository $lostPropertyLogRepository,
        private readonly RestaurantLogRepository $restaurantLogRepository,
        private readonly AnnouncementRepository $announcementRepository,
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function getDashboardData(Hotel $hotel): array
    {
        return [
            'attention' => $this->getAttentionQueue($hotel),
            'pulse' => $this->getWeeklyPulse($hotel),
            'activity' => $this->getRecentActivity($hotel),
            'announcements' => $this->getAnnouncements(),
            'events' => $this->getUpcomingEvents($hotel),
        ];
    }

    private function canView(Module $module): bool
    {
        return $this->security->isGranted($module->permission('view'));
    }

    /**
     * @return array<array{module: Module, count: int, label: string, icon: string, route: string}>
     */
    private function getAttentionQueue(Hotel $hotel): array
    {
        $queue = [];

        if ($this->canView(Module::PACKAGES)) {
            $queue[] = [
                'module' => Module::PACKAGES,
                'count' => $this->packageRepository->count(['hotel' => $hotel, 'status' => PackageStatus::RECEIVED]),
                'label' => 'Packages awaiting collection',
                'icon' => 'ph-package',
                'route' => 'aureum_packages',
            ];
        }

        if ($this->canView(Module::BOOKINGS)) {
            $queue[] = [
                'module' => Module::BOOKINGS,
                'count' => $this->bookingRepository->count(['hotel' => $hotel, 'status' => BookingStatus::UNCONFIRMED]),
                'label' => 'Bookings awaiting confirmation',
                'icon' => 'ph-bookmark-simple',
                'route' => 'aureum_bookings',
            ];
            $queue[] = [
                'module' => Module::BOOKINGS,
                'count' => $this->countBookingsToday($hotel),
                'label' => 'Confirmed bookings due today',
                'icon' => 'ph-calendar-check',
                'route' => 'aureum_bookings',
            ];
        }

        if ($this->canView(Module::FINES)) {
            $queue[] = [
                'module' => Module::FINES,
                'count' => $this->fineRepository->count(['hotel' => $hotel, 'status' => FineStatus::APPEAL_SUBMITTED]),
                'label' => 'Fine appeals to review',
                'icon' => 'ph-scales',
                'route' => 'aureum_fines',
            ];
        }

        if ($this->canView(Module::LOST_PROPERTY)) {
            $queue[] = [
                'module' => Module::LOST_PROPERTY,
                'count' => $this->lostPropertyRepository->count(['hotel' => $hotel, 'status' => [
                    LostPropertyStatus::OPEN,
                    LostPropertyStatus::WAITING_COLLECTION,
                    LostPropertyStatus::WAITING_POSTED,
                ]]),
                'label' => 'Lost property unresolved',
                'icon' => 'ph-magnifying-glass',
                'route' => 'aureum_lost_property',
            ];
        }

        return $queue;
    }

    private function countBookingsToday(Hotel $hotel): int
    {
        $start = new DateTime('today');
        $end = new DateTime('tomorrow');

        return (int)$this->bookingRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.hotel = :hotel')
            ->andWhere('b.status = :status')
            ->andWhere('b.date >= :start')
            ->andWhere('b.date < :end')
            ->setParameter('hotel', $hotel)
            ->setParameter('status', BookingStatus::CONFIRMED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<array{module: Module, thisWeek: int, lastWeek: int, delta: int}>
     */
    private function getWeeklyPulse(Hotel $hotel): array
    {
        $now = new DateTime();
        $weekAgo = new DateTime('-7 days');
        $twoWeeksAgo = new DateTime('-14 days');

        $pulse = [];
        foreach ($this->getLogRepositories() as $module) {
            [$moduleEnum, $repository] = $module;
            if (!$this->canView($moduleEnum)) {
                continue;
            }

            $thisWeek = $repository->countByHotelBetween($hotel, $weekAgo, $now);
            $lastWeek = $repository->countByHotelBetween($hotel, $twoWeeksAgo, $weekAgo);

            $pulse[] = [
                'module' => $moduleEnum,
                'thisWeek' => $thisWeek,
                'lastWeek' => $lastWeek,
                'delta' => $thisWeek - $lastWeek,
            ];
        }

        return $pulse;
    }

    /**
     * @return array<array{module: Module, log: object}>
     */
    private function getRecentActivity(Hotel $hotel): array
    {
        $entries = [];
        foreach ($this->getLogRepositories() as $module) {
            [$moduleEnum, $repository] = $module;
            if (!$this->canView($moduleEnum)) {
                continue;
            }

            foreach ($repository->findRecentByHotel($hotel, self::ACTIVITY_LIMIT) as $log) {
                $entries[] = ['module' => $moduleEnum, 'log' => $log];
            }
        }

        usort($entries, static fn (array $a, array $b) => $b['log']->getCreatedAt() <=> $a['log']->getCreatedAt());

        return array_slice($entries, 0, self::ACTIVITY_LIMIT);
    }

    private function getAnnouncements(): array
    {
        $announcements = $this->announcementRepository->findBy([], ['createdAt' => 'DESC'], 6);

        $visible = array_filter(
            $announcements,
            fn (object $announcement) => $this->canView($announcement->getModule()),
        );

        return array_slice(array_values($visible), 0, 3);
    }

    private function getUpcomingEvents(Hotel $hotel): array
    {
        if (!$this->canView(Module::EVENTS)) {
            return [];
        }

        return $this->eventRepository->createQueryBuilder('e')
            ->where('e.hotel = :hotel')
            ->andWhere('e.start >= :now')
            ->setParameter('hotel', $hotel)
            ->setParameter('now', new DateTimeImmutable('today'))
            ->orderBy('e.start', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{0: Module, 1: AbstractLogRepository}>
     */
    private function getLogRepositories(): array
    {
        return [
            [Module::PACKAGES, $this->packageLogRepository],
            [Module::BOOKINGS, $this->bookingLogRepository],
            [Module::FINES, $this->fineLogRepository],
            [Module::LOST_PROPERTY, $this->lostPropertyLogRepository],
            [Module::RESTAURANTS, $this->restaurantLogRepository],
        ];
    }
}
