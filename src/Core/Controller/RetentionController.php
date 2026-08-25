<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Repository\RetentionPolicyRepository;
use Citadel\Aureum\Core\Security\AureumVoter;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\RetentionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Range;

#[Route('/retention', name: 'retention_')]
#[IsGranted(AureumVoter::RBAC_MANAGE)]
class RetentionController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly RetentionPolicyRepository $policyRepository,
        private readonly RetentionService $retentionService,
    ) {
    }

    #[Route('', name: 'edit')]
    public function edit(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createNotFoundException();
        }

        $modules = array_filter(
            Module::cases(),
            static fn (Module $module): bool => RetentionService::supports($module),
        );

        $existing = $this->policyRepository->findByHotelKeyedByModule($hotel);

        $defaults = [];
        foreach ($modules as $module) {
            $policy = $existing[$module->value] ?? null;
            $defaults[$module->value] = $policy?->getRetainForMonths();
        }

        $builder = $this->createFormBuilder($defaults);
        foreach ($modules as $module) {
            $builder->add($module->value, IntegerType::class, [
                'label' => $module->getLabel(),
                'required' => false,
                'help' => 'Months to keep guest details. Leave blank to keep them indefinitely.',
                'constraints' => [
                    new Range(min: 1, max: 240, notInRangeMessage: 'Choose between 1 and 240 months.'),
                ],
                'attr' => ['min' => 1, 'max' => 240, 'placeholder' => 'No limit'],
            ]);
        }

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employee = $this->aureumService->getEmployee();

            foreach ($form->getData() as $moduleValue => $months) {
                $module = Module::tryFrom((string)$moduleValue);
                if ($module === null) {
                    continue;
                }

                $policy = $this->policyRepository->findOrCreate($hotel, $module);
                if ($policy->getRetainForMonths() === $months) {
                    continue;
                }

                $policy->setRetainForMonths($months === null ? null : (int)$months);
                $policy->setUpdatedBy($employee);
                $this->policyRepository->save($policy);
            }

            $this->addFlash('success', 'Retention periods updated.');

            return $this->redirectToRoute('aureum_retention_edit');
        }

        $pending = [];
        foreach ($existing as $moduleValue => $policy) {
            if (!$policy->isEnforced()) {
                continue;
            }

            $count = $this->retentionService->applyPolicy($policy, true);
            if ($count > 0) {
                $pending[$moduleValue] = $count;
            }
        }

        return $this->render('@CitadelAureum/core/retention.html.twig', [
            'form' => $form,
            'policies' => $existing,
            'pending' => $pending,
        ]);
    }
}
