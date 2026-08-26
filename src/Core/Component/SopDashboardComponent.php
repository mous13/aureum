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

    /** @var array<array{sop: Sop, standing: SopStanding}>|null */
    private ?array $publishedRows = null;

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

        if (!$this->hasFilters() && !$this->canManage()) {
            return $this->rows = $this->getPublishedRows();
        }

        $sops = $this->sopRepository->searchForHotel(
            $this->aureumService->getHotel(),
            trim($this->search),
            $this->categoryId,
            $this->canManage(),
            $this->canManage() && $this->showArchived,
        );

        return $this->rows = $this->buildRows($sops);
    }

    /** @return array<array{sop: Sop, standing: SopStanding}> */
    public function getAttention(): array
    {
        return array_values(array_filter(
            $this->getPublishedRows(),
            static fn (array $row) => $row['standing']->needsAction(),
        ));
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
        $sops = array_map(static fn (array $row) => $row['sop'], $this->getRows());
        $sops = array_filter($sops, static fn (Sop $sop) => $sop->getStatus() === SopStatus::PUBLISHED);
        if ($sops === []) {
            return [];
        }

        $employees = $this->employeeRepository->findByHotel($hotel);
        $signOffs = $this->signOffRepository->findCurrentVersionSignOffsForSops($sops);

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
                $signOff = $signOffs[$sop->getId()][$employee->getId()] ?? null;
                if ($this->standingService->standingFor($sop, $employee, $now, $signOff) === SopStanding::CURRENT) {
                    $signed++;
                }
            }

            $counts[$sop->getId()] = ['signed' => $signed, 'expected' => $expected];
        }

        return $counts;
    }

    public function getUserTimezone(): string
    {
        return $this->aureumService->getEmployee()?->getUser()?->getTimezone() ?? 'UTC';
    }

    private function hasFilters(): bool
    {
        return trim($this->search) !== '' || $this->categoryId !== null;
    }

    /** @return array<array{sop: Sop, standing: SopStanding}> */
    private function getPublishedRows(): array
    {
        if ($this->publishedRows !== null) {
            return $this->publishedRows;
        }

        $sops = $this->sopRepository->searchForHotel($this->aureumService->getHotel(), '', null, false, false);

        return $this->publishedRows = $this->buildRows($sops);
    }

    /**
     * @param array<Sop> $sops
     * @return array<array{sop: Sop, standing: SopStanding}>
     */
    private function buildRows(array $sops): array
    {
        $employee = $this->aureumService->getEmployee();
        $signOffs = $this->signOffRepository->findCurrentVersionSignOffs($employee, $sops);
        $now = new DateTimeImmutable();

        $rows = [];
        foreach ($sops as $sop) {
            $rows[] = [
                'sop' => $sop,
                'standing' => $this->standingService->standingFor($sop, $employee, $now, $signOffs[$sop->getId()] ?? null),
            ];
        }

        return $rows;
    }
}
