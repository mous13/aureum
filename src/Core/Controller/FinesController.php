<?php

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Form\FineEditType;
use Citadel\Aureum\Core\Form\FineType;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\FineRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;


class FinesController extends AbstractController
{
    public function __construct(
        readonly FineRepository $fineRepository,
        readonly Security $security,
        readonly EmployeeRepository $employeeRepository,
    ){
    }

    #[Route('/fines', name: 'fines')]
    public function index(Request $request): Response
    {
        $user = $this->security->getUser();
        $employee = $this->employeeRepository->findOneBy(['user' => $user]);
        $hotel = $employee->getHotel();
        $fines = $this->fineRepository->findBy(['hotel' => $hotel]);

        $fine = new Fine();
        $form = $this->createForm(FineType::class, $fine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fine = $form->getData();

            $fine->setCreatedAt(new \DateTime());
            $fine->setCreatedBy($employee);
            $fine->setHotel($hotel);
            $this->fineRepository->save($fine);

            return $this->redirectToRoute('aureum_fines');
        }

        $editForms = [];
        foreach ($fines as $fne) {
            $editForm = $this->createForm(FineEditType::class, $fne, [
                'action' => $this->generateUrl('aureum_fines_edit', ['id' => $fne->getId()])
            ]);
            $editForms[$fne->getId()] = $editForm->createView();
        }

        return $this->render('@CitadelAureum/core/fines/fines.html.twig',[
            'fines' => $fines,
            'form' => $form,
            'editForms' => $editForms,
        ]);
    }

    #[Route('/fines/{id}/edit', name: 'fines_edit')]
    public function edit(Request $request, Fine $fine): Response
    {
        $form = $this->createForm(FineEditType::class, $fine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fine->setUpdatedAt(new \DateTime());

            $this->fineRepository->save($fine);

            $this->addFlash('success', 'Fine updated');
        }
        return $this->redirectToRoute('aureum_fines');
    }
}