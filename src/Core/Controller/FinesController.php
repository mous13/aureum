<?php

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Form\FineEditType;
use Citadel\Aureum\Core\Form\FineType;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\FineLogRepository;
use Citadel\Aureum\Core\Repository\FineRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\FineLogService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;


class FinesController extends AbstractController
{
    public function __construct(
        private readonly FineRepository $fineRepository,
        private readonly Security $security,
        private readonly EmployeeRepository $employeeRepository,
        private readonly FineLogService $logService,
        private readonly FineLogRepository $fineLogRepository,
        private readonly AureumService $aureumService,
    ){
    }

    #[Route('/fines', name: 'fines')]
    public function index(Request $request): Response
    {
        $user = $this->security->getUser();
        $employee = $this->employeeRepository->findOneBy(['user' => $user]);
        $hotel = $employee->getHotel();
        $fines = $this->fineRepository->findAllOrderedByDate($hotel);

        $fine = new Fine();
        $form = $this->createForm(FineType::class, $fine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fine = $form->getData();

            $fine->setCreatedAt(new \DateTime());
            $fine->setCreatedBy($employee);
            $fine->setHotel($hotel);
            $this->fineRepository->save($fine);

            $this->logService->logCreated($fine, $employee);

            return $this->redirectToRoute('aureum_fines');
        }

        $editForms = [];
        foreach ($fines as $fne) {
            $editForm = $this->createForm(FineEditType::class, $fne, [
                'action' => $this->generateUrl('aureum_fines_edit', ['id' => $fne->getId()])
            ]);
            $editForms[$fne->getId()] = $editForm->createView();
        }

        $fineLogs = [];
        foreach ($fines as $fne) {
            $fineLogs[$fne->getId()] = $this->fineLogRepository->findByFine($fne);
        }

        return $this->render('@CitadelAureum/core/fines/fines.html.twig',[
            'fines' => $fines,
            'form' => $form,
            'editForms' => $editForms,
            'logs' => $fineLogs,
        ]);
    }

    #[Route('/fines/{id}/edit', name: 'fines_edit')]
    public function edit(Request $request, Fine $fine): Response
    {
        $originalData = $this->logService->captureCurrentState($fine);
        $employee = $this->aureumService->getEmployee();

        $form = $this->createForm(FineEditType::class, $fine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fine->setUpdatedAt(new \DateTime());

            $this->fineRepository->save($fine);

            $this->logService->logUpdated($fine, $originalData, $employee);

            $this->addFlash('success', 'Fine updated');
        }
        return $this->redirectToRoute('aureum_fines');
    }
}