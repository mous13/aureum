<?php

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Transfer;
use Citadel\Aureum\Core\Form\TransferEditType;
use Citadel\Aureum\Core\Form\TransferType;
use Citadel\Aureum\Core\Repository\TransferRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;


class TransfersController extends AbstractController
{
    public function __construct(
        private readonly TransferRepository $transferRepository,
        private readonly Security $security,
        private readonly AureumService $aureumService
    ){
    }

    #[Route('/transfers', name: 'transfers')]
    public function index(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        $transfers = $this->transferRepository->findBy(['hotel' => $hotel]);

        $transfer = new Transfer();
        $form = $this->createForm(TransferType::class, $transfer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $transfer = $form->getData();

            $transfer->setHotel($hotel);
            $this->transferRepository->save($transfer);

            return $this->redirectToRoute('aureum_transfers');
        }

        $editForms = [];
        foreach ($transfers as $tran) {
            $editForm = $this->createForm(TransferEditType::class, $tran, [
                'action' => $this->generateUrl('aureum_transfers_edit', ['id' => $tran->getId()])
            ]);
            $editForms[$tran->getId()] = $editForm->createView();
        }

        return $this->render('@CitadelAureum/core/transfers/transfers.html.twig',[
            'transfers' => $transfers,
            'form' => $form,
            'editForms' => $editForms,
            'security' => $this->security,
            'employee' => $this->aureumService->getEmployee(),
        ]);
    }

    #[Route('/transfers/{id}/edit', name: 'transfers_edit')]
    public function edit(Request $request, Transfer $transfer): Response
    {
        $form = $this->createForm(TransferEditType::class, $transfer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->transferRepository->save($transfer);
            $this->addFlash('success', 'Transfer updated');
        }
        return $this->redirectToRoute('aureum_transfers');
    }


}