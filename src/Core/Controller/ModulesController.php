<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Citadel\Aureum\Core\Security\AureumVoter;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/modules', name: 'modules_')]
#[IsGranted(AureumVoter::RBAC_MANAGE)]
class ModulesController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly HotelRepository $hotelRepository,
    ) {
    }

    #[Route('', name: 'edit')]
    public function edit(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();

        $choices = [];
        foreach (Module::cases() as $module) {
            $choices[$module->getLabel()] = $module->value;
        }

        $form = $this->createFormBuilder(['modules' => $hotel->getEnabledModules()])
            ->add('modules', ChoiceType::class, [
                'label' => 'Enabled modules',
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'help' => 'Disabled modules are hidden from every employee of your hotel, including admins.',
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hotel->setEnabledModules($form->getData()['modules']);
            $this->hotelRepository->save($hotel);
            $this->addFlash('success', 'Modules updated');

            return $this->redirectToRoute('aureum_modules_edit');
        }

        return $this->render('@CitadelAureum/core/modules.html.twig', [
            'form' => $form,
        ]);
    }
}
