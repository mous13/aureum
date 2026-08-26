<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Enum\SopStanding;
use Citadel\Aureum\Core\Entity\Enum\SopStatus;
use Citadel\Aureum\Core\Entity\Sop;
use Citadel\Aureum\Core\Entity\SopSignOff;
use Citadel\Aureum\Core\Form\SopType;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\SopRepository;
use Citadel\Aureum\Core\Repository\SopSignOffRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\SopStandingService;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sops', name: 'sops_')]
#[IsGranted('aureum.module.sops.view')]
class SopsController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly SopRepository $sopRepository,
        private readonly SopSignOffRepository $signOffRepository,
        private readonly SopStandingService $standingService,
        private readonly EmployeeRepository $employeeRepository,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('@CitadelAureum/core/sops/index.html.twig');
    }

    #[Route('/sop/{id}', name: 'view', requirements: ['id' => '\d+'])]
    public function view(int $id): Response
    {
        $sop = $this->findSop($id);
        if ($sop->getStatus() !== SopStatus::PUBLISHED) {
            $this->denyAccessUnlessGranted('aureum.module.sops.manage');
        }

        $employee = $this->aureumService->getEmployee();
        $signOff = $this->signOffRepository->findForCurrentVersion($sop, $employee);

        return $this->render('@CitadelAureum/core/sops/view.html.twig', [
            'sop' => $sop,
            'signOff' => $signOff,
            'standing' => $this->standingService->standingFor($sop, $employee, new DateTimeImmutable(), $signOff),
            'userTimezone' => $this->userTimezone(),
        ]);
    }

    #[Route('/sop/{id}/sign', name: 'sign', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sign(Request $request, int $id): Response
    {
        $sop = $this->findSop($id);
        if (!$sop->getStatus()->isActionable()) {
            throw $this->createNotFoundException();
        }

        $token = (string)$request->request->get('_token');
        if (!$this->isCsrfTokenValid('aureum_sop_sign_' . $sop->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($request->request->getInt('version') !== $sop->getVersion()) {
            $this->addFlash('error', 'This procedure was updated while you were reading it. Please review the current version before signing.');

            return $this->redirectToRoute('aureum_sops_view', ['id' => $sop->getId()]);
        }

        $employee = $this->aureumService->getEmployee();
        $signOff = new SopSignOff();
        $signOff->setSop($sop);
        $signOff->setEmployee($employee);
        $signOff->setHotel($sop->getHotel());
        $signOff->setVersion($sop->getVersion());
        $this->signOffRepository->save($signOff);

        $this->addFlash('success', 'Sign-off recorded. Thank you.');

        return $this->redirectToRoute('aureum_sops_view', ['id' => $sop->getId()]);
    }

    #[Route('/new', name: 'new')]
    #[IsGranted('aureum.module.sops.manage')]
    public function new(Request $request): Response
    {
        $sop = new Sop();

        return $this->handleForm($request, $sop, true);
    }

    #[Route('/sop/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    #[IsGranted('aureum.module.sops.manage')]
    public function edit(Request $request, int $id): Response
    {
        return $this->handleForm($request, $this->findSop($id), false);
    }

    #[Route('/sop/{id}/archive', name: 'archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('aureum.module.sops.manage')]
    public function archive(Request $request, int $id): Response
    {
        return $this->changeStatus($request, $id, SopStatus::ARCHIVED, 'SOP archived.');
    }

    #[Route('/sop/{id}/unarchive', name: 'unarchive', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('aureum.module.sops.manage')]
    public function unarchive(Request $request, int $id): Response
    {
        return $this->changeStatus($request, $id, SopStatus::PUBLISHED, 'SOP restored.');
    }

    #[Route('/sop/{id}/compliance', name: 'compliance', requirements: ['id' => '\d+'])]
    #[IsGranted('aureum.module.sops.manage')]
    public function compliance(int $id): Response
    {
        $sop = $this->findSop($id);
        $now = new DateTimeImmutable();

        $rows = [];
        $counts = [SopStanding::CURRENT->value => 0, SopStanding::SIGN_OFF_NEEDED->value => 0, SopStanding::RECHECK_DUE->value => 0];
        $signOffs = $this->signOffRepository->findCurrentVersionSignOffsForSop($sop);
        foreach ($this->employeeRepository->findByHotel($sop->getHotel()) as $employee) {
            if (!$this->standingService->inAudience($sop, $employee)) {
                continue;
            }

            $signOff = $signOffs[$employee->getId()] ?? null;
            $standing = $this->standingService->standingFor($sop, $employee, $now, $signOff);
            $rows[] = ['employee' => $employee, 'standing' => $standing, 'signOff' => $signOff];
            if (isset($counts[$standing->value])) {
                $counts[$standing->value]++;
            }
        }

        return $this->render('@CitadelAureum/core/sops/compliance.html.twig', [
            'sop' => $sop,
            'rows' => $rows,
            'counts' => $counts,
            'history' => $this->signOffRepository->findForSop($sop),
            'userTimezone' => $this->userTimezone(),
        ]);
    }

    private function handleForm(Request $request, Sop $sop, bool $isNew): Response
    {
        $employee = $this->aureumService->getEmployee();
        $status = $isNew ? SopStatus::DRAFT : $sop->getStatus();

        $form = $this->createForm(SopType::class, $sop, [
            'hotel' => $employee->getHotel(),
            'is_published' => $status === SopStatus::PUBLISHED,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $sop->setHotel($employee->getHotel());
                $sop->setCreatedBy($employee);
            }
            $sop->setUpdatedBy($employee);
            $sop->setUpdatedAt(new DateTime());

            if ($status === SopStatus::PUBLISHED) {
                if ($form->get('changeKind')->getData() === 'new_version') {
                    $sop->bumpVersion();
                    $this->addFlash('success', "Published as version {$sop->getVersion()}. Everyone must sign off again.");
                } else {
                    $this->addFlash('success', 'Changes saved. Existing sign-offs stand.');
                }
            } elseif ($status === SopStatus::ARCHIVED) {
                $this->addFlash('success', 'Changes saved. The SOP remains archived.');
            } elseif ($request->request->get('publish') === '1') {
                $sop->publish();
                $this->addFlash('success', 'SOP published.');
            } else {
                $this->addFlash('success', 'Draft saved.');
            }

            $this->sopRepository->save($sop);

            return $this->redirectToRoute('aureum_sops_view', ['id' => $sop->getId()]);
        }

        return $this->render('@CitadelAureum/core/sops/form.html.twig', [
            'form' => $form,
            'sop' => $isNew ? null : $sop,
            'isPublished' => $status === SopStatus::PUBLISHED,
            'isArchived' => $status === SopStatus::ARCHIVED,
        ]);
    }

    private function changeStatus(Request $request, int $id, SopStatus $status, string $message): Response
    {
        $sop = $this->findSop($id);

        $token = (string)$request->request->get('_token');
        if (!$this->isCsrfTokenValid('aureum_sop_status_' . $sop->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($status === SopStatus::PUBLISHED) {
            $sop->publish();
        } else {
            $sop->setStatus($status);
        }
        $this->sopRepository->save($sop);
        $this->addFlash('success', $message);

        return $this->redirectToRoute('aureum_sops_view', ['id' => $sop->getId()]);
    }

    private function userTimezone(): string
    {
        return $this->aureumService->getEmployee()?->getUser()?->getTimezone() ?? 'UTC';
    }

    private function findSop(int $id): Sop
    {
        $sop = $this->sopRepository->findOneBy(['id' => $id, 'hotel' => $this->aureumService->getHotel()]);
        if ($sop === null) {
            throw $this->createNotFoundException();
        }

        return $sop;
    }
}
