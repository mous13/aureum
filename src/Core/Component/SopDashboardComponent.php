<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Enum\SopStanding;
use Citadel\Aureum\Core\Entity\Enum\SopStatus;
use Citadel\Aureum\Core\Entity\Sop;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\SopCategoryRepository;
use Citadel\Aureum\Core\Repository\SopRepository;
use Citadel\Aureum\Core\Repository\SopSignOffRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\SopStandingService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Aureum\\SopDashboard', '@CitadelAureum/core/components/sop_dashboard.html.twig')]
#[IsGranted('aureum.module.sops.view')]
class SopDashboardComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public ?int $categoryId = null;

    #[LiveProp(writable: true)]
    public bool $showArchived = false;

    /** @var array<array{sop: Sop, standing: SopStanding}>|null */
    private ?array $rows = null;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly SopRepository $sopRepository,
        private readonly SopCategoryRepository $categoryRepository,
        private readonly SopSignOffRepository $signOffRepository,
        private readonly SopStandingService $standingService,
        private readonly EmployeeRepository $employeeRepository,
    ) {
    }

    #[LiveAction]
    public function setCategory(#[LiveArg] ?int $categoryId = null): void
    {
        $this->categoryId = $categoryId;
    }

    public function canManage(): bool
    {
        return $this->isGranted('aureum.module.sops.manage');
    }

    /** @return array<array{sop: Sop, standing: SopStanding}> */
    public function getRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $employee = $this->aureumService->getEmployee();
        $sops = $this->sopRepository->searchForHotel(
            $employee->getHotel(),
            trim($this->search),
            $this->categoryId,
            $this->canManage(),
            $this->canManage() && $this->showArchived,
        );

        $signOffs = $this->signOffRepository->findCurrentVersionSignOffs($employee, $sops);
        $now = new DateTimeImmutable();

        $rows = [];
        foreach ($sops as $sop) {
            $rows[] = [
                'sop' => $sop,
                'standing' => $this->standingService->standingFor($sop, $employee, $now, $signOffs[$sop->getId()] ?? null),
            ];
        }

        return $this->rows = $rows;
    }

    /** @return array<array{sop: Sop, standing: SopStanding}> */
    public function getAttention(): array
    {
        $employee = $this->aureumService->getEmployee();
        $sops = $this->sopRepository->searchForHotel($employee->getHotel(), '', null, false, false);
        $signOffs = $this->signOffRepository->findCurrentVersionSignOffs($employee, $sops);
        $now = new DateTimeImmutable();

        $attention = [];
        foreach ($sops as $sop) {
            $standing = $this->standingService->standingFor($sop, $employee, $now, $signOffs[$sop->getId()] ?? null);
            if ($standing->needsAction()) {
                $attention[] = ['sop' => $sop, 'standing' => $standing];
            }
        }

        return $attention;
    }

    /** @return array<\Citadel\Aureum\Core\Entity\SopCategory> */
    public function getCategories(): array
    {
        return $this->categoryRepository->findBy(
            ['hotel' => $this->aureumService->getHotel()],
            ['name' => 'ASC'],
        );
    }

    /** @return array<int, array{signed: int, expected: int}> */
    public function getComplianceCounts(): array
    {
        if (!$this->canManage()) {
            return [];
        }

        $hotel = $this->aureumService->getHotel();
        $employees = $this->employeeRepository->findBy(['hotel' => $hotel, 'archivedAt' => null]);
        $sops = array_map(static fn (array $row) => $row['sop'], $this->getRows());
        $sops = array_filter($sops, static fn (Sop $sop) => $sop->getStatus() !== SopStatus::DRAFT);
        if ($sops === []) {
            return [];
        }

        $signOffsBySopAndEmployee = [];
        foreach ($this->signOffRepository->findBy(['sop' => $sops]) as $signOff) {
            if ($signOff->getVersion() === $signOff->getSop()->getVersion()) {
                $signOffsBySopAndEmployee[$signOff->getSop()->getId()][$signOff->getEmployee()->getId()] = $signOff;
            }
        }

        $now = new DateTimeImmutable();
        $counts = [];
        foreach ($sops as $sop) {
            $signed = 0;
            $expected = 0;
            foreach ($employees as $employee) {
                if (!$this->standingService->inAudience($sop, $employee)) {
                    continue;
                }

                $expected++;
                $signOff = $signOffsBySopAndEmployee[$sop->getId()][$employee->getId()] ?? null;
                if ($this->standingService->standingFor($sop, $employee, $now, $signOff) === SopStanding::CURRENT) {
                    $signed++;
                }
            }

            $counts[$sop->getId()] = ['signed' => $signed, 'expected' => $expected];
        }

        return $counts;
    }
}
