<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Enum\FineStatus;
use Citadel\Aureum\Core\Entity\Enum\LostPropertyStatus;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Enum\PackageStatus;
use Citadel\Aureum\Core\Entity\Enum\TransferStatus;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Repository\AbstractLogRepository;
use Citadel\Aureum\Core\Repository\AnnouncementRepository;
use Citadel\Aureum\Core\Repository\EventRepository;
use Citadel\Aureum\Core\Repository\FineLogRepository;
use Citadel\Aureum\Core\Repository\FineRepository;
use Citadel\Aureum\Core\Repository\LostPropertyLogRepository;
use Citadel\Aureum\Core\Repository\LostPropertyRepository;
use Citadel\Aureum\Core\Repository\PackageLogRepository;
use Citadel\Aureum\Core\Repository\PackageRepository;
use Citadel\Aureum\Core\Repository\RestaurantLogRepository;
use Citadel\Aureum\Core\Repository\TransferLogRepository;
use Citadel\Aureum\Core\Repository\TransferRepository;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardService
{
    private const ACTIVITY_LIMIT = 10;

    public function __construct(
        private readonly Security $security,
        private readonly PackageRepository $packageRepository,
        private readonly TransferRepository $transferRepository,
        private readonly FineRepository $fineRepository,
        private readonly LostPropertyRepository $lostPropertyRepository,
        private readonly PackageLogRepository $packageLogRepository,
        private readonly TransferLogRepository $transferLogRepository,
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

        if ($this->canView(Module::TRANSFERS)) {
            $queue[] = [
                'module' => Module::TRANSFERS,
                'count' => $this->transferRepository->count(['hotel' => $hotel, 'status' => TransferStatus::UNCONFIRMED]),
                'label' => 'Transfers awaiting confirmation',
                'icon' => 'ph-car',
                'route' => 'aureum_transfers',
            ];
            $queue[] = [
                'module' => Module::TRANSFERS,
                'count' => $this->countTransfersToday($hotel),
                'label' => 'Confirmed transfers due today',
                'icon' => 'ph-calendar-check',
                'route' => 'aureum_transfers',
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

    private function countTransfersToday(Hotel $hotel): int
    {
        $start = new DateTime('today');
        $end = new DateTime('tomorrow');

        return (int)$this->transferRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.hotel = :hotel')
            ->andWhere('t.status = :status')
            ->andWhere('t.date >= :start')
            ->andWhere('t.date < :end')
            ->setParameter('hotel', $hotel)
            ->setParameter('status', TransferStatus::CONFIRMED)
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
            [Module::TRANSFERS, $this->transferLogRepository],
            [Module::FINES, $this->fineLogRepository],
            [Module::LOST_PROPERTY, $this->lostPropertyLogRepository],
            [Module::RESTAURANTS, $this->restaurantLogRepository],
        ];
    }
}
