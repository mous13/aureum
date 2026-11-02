<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Form\BarType;
use Citadel\Aureum\Core\Repository\BarRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\BarLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bars', name: 'bars_')]
#[IsGranted('aureum.module.bars.view')]
class BarsController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly BarRepository $barRepository,
        private readonly BarLogService $logService,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    #[Route('/list', name: 'list')]
    public function list(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();

        $form = $this->createForm(BarType::class, new Bar(), ['hotel' => $hotel]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('aureum.module.bars.manage');

            $bar = $form->getData();
            $bar->setHotel($hotel);
            $this->barRepository->save($bar);
            $this->logService->logCreated($bar, $this->aureumService->getEmployee());
            $this->addFlash('success', 'Bar Created');

            return $this->redirectToRoute('aureum_bars_list');
        }

        return $this->render('@CitadelAureum/core/bars/bars.html.twig', [
            'hotelId' => $hotel->getId(),
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    #[IsGranted('aureum.module.bars.manage')]
    public function edit(Request $request, Bar $bar): Response
    {
        $hotel = $this->aureumService->getHotel();
        $originalData = $this->logService->captureCurrentState($bar);

        $form = $this->formFactory->createNamed('bar_' . $bar->getId(), BarType::class, $bar, ['hotel' => $hotel]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bar->setUpdatedAt(new \DateTime());
            $this->barRepository->save($bar);
            $this->logService->logUpdated($bar, $originalData, $this->aureumService->getEmployee());
            $this->addFlash('success', 'Bar Updated');
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Bar could not be updated, please check the details and try again.');
        }

        return $this->redirectToRoute('aureum_bars_list');
    }
}
