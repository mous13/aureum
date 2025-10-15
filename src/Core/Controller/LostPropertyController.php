<?php

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Entity\LostPropertyClass;
use Citadel\Aureum\Core\Form\LostPropertyEditType;
use Citadel\Aureum\Core\Form\LostPropertyType;
use Citadel\Aureum\Core\Repository\LostPropertyRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;

class LostPropertyController extends AbstractController
{
    public function __construct(
        private readonly LostPropertyRepository $lostPropertyRepository,
        private readonly Security $security,
        private readonly AureumService $aureumService,
    ){
    }

    #[Route('/lostproperty', name: 'lost_property')]
    public function index(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        $lostProperties = $this->lostPropertyRepository->findBy(['hotel' => $hotel]);

        $lostProperty = new LostProperty();
        $form = $this->createForm(LostPropertyType::class, $lostProperty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lostProperty = $form->getData();

            $lostProperty->setReportedBy($this->aureumService->getEmployee());
            $lostProperty->setHotel($hotel);
            $this->lostPropertyRepository->save($lostProperty);

            return $this->redirectToRoute('aureum_lost_property');
        }
        $editForms = [];
        foreach ($lostProperties as $lp) {
            $editForm = $this->createForm(LostPropertyEditType::class, $lp, [
                'action' => $this->generateUrl('aureum_lost_property_edit', ['id' => $lp->getId()])
            ]);
            $editForms[$lp->getId()] = $editForm->createView();
        }

        return $this->render('@CitadelAureum/core/lostproperty/lost_property.html.twig', [
            'lostProperties' => $lostProperties,
            'form' => $form,
            'editForms' => $editForms,
        ]);
    }

    #[Route('/lostproperty/{id}/edit', name: 'lost_property_edit')]
    public function edit(Request $request, LostProperty $lostProperty): Response
    {
        $form = $this->createForm(lostPropertyEditType::class, $lostProperty);
        $form->handleRequest($request);
        $lostProperty = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->lostPropertyRepository->save($lostProperty);
            $this->addFlash('success', 'Lost property has been edited.');
        }
        return $this->redirectToRoute('aureum_lost_property');
    }
}