<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class FirstLoginPasswordController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly EmployeeRepository $employeeRepository,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/first-login', name: 'first_login_password')]
    #[IsGranted('ROLE_USER')]
    public function __invoke(Request $request): Response
    {
        $employee = $this->aureumService->getEmployee();
        if ($employee === null || !$employee->mustChangePassword()) {
            return $this->redirectToRoute('aureum_dashboard');
        }

        $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'New password',
                    'help' => 'At least 12 characters. Use something only you know.',
                ],
                'second_options' => ['label' => 'Confirm new password'],
                'invalid_message' => 'Both passwords must match.',
                'constraints' => [
                    new NotBlank(message: 'Choose a password.'),
                    new Length(
                        min: 12,
                        max: 4096,
                        minMessage: 'Use at least {{ limit }} characters.',
                    ),
                    new NotCompromisedPassword(
                        message: 'That password has appeared in a known data breach. Choose another.',
                        skipOnError: true,
                    ),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User|null $user */
            $user = $employee->getUser();
            if ($user === null) {
                throw $this->createAccessDeniedException();
            }

            $user->setPassword($this->passwordHasher->hashPassword($user, $form->get('password')->getData()));
            $this->userRepository->save($user);

            $employee->setMustChangePassword(false);
            $this->employeeRepository->save($employee);

            $this->addFlash('success', 'Password set. Welcome to Aureum.');

            return $this->redirectToRoute('aureum_dashboard');
        }

        return $this->render('@CitadelAureum/core/first_login.html.twig', [
            'form' => $form,
            'employee' => $employee,
        ]);
    }
}
