<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Form\NewStaffType;
use Citadel\Aureum\Core\Form\DTO\NewStaff;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Security\AureumVoter;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\PasswordGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Entity\User;
use Forumify\Core\Exception\UserAlreadyExistsException;
use Forumify\Core\Form\DTO\NewUser;
use Forumify\Core\Repository\UserRepository;
use Forumify\Core\Service\CreateUserService;
use Forumify\Core\Service\Mailer;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff', name: 'staff_')]
#[IsGranted(AureumVoter::EMPLOYEE_MANAGE)]
class StaffController extends AbstractController
{
    public function __construct(
        private readonly AureumService $aureumService,
        private readonly EmployeeRepository $employeeRepository,
        private readonly UserRepository $userRepository,
        private readonly CreateUserService $createUserService,
        private readonly PasswordGenerator $passwordGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('@CitadelAureum/core/staff/list.html.twig', [
            'employees' => $this->employeeRepository->findByHotel($hotel),
            'canManageRoles' => $this->isGranted(AureumVoter::RBAC_MANAGE),
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): Response
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createNotFoundException();
        }

        $data = new NewStaff();
        $form = $this->createForm(NewStaffType::class, $data, ['hotel' => $hotel]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->passwordGenerator->generate();

            $newUser = new NewUser();
            $newUser->setUsername($data->getUsername());
            $newUser->setEmail($data->getEmail());
            $newUser->setPassword($password);
            $newUser->setTimezone($data->getTimezone());

            try {
                $user = $this->createUserService->createUser($newUser, false);
            } catch (UserAlreadyExistsException) {
                $form->get('username')->addError(
                    new \Symfony\Component\Form\FormError('That username or email is already taken.')
                );

                return $this->render('@CitadelAureum/core/staff/create.html.twig', ['form' => $form]);
            }

            $employee = new Employee();
            $employee->setName($data->getName());
            $employee->setUser($user);
            $employee->setHotel($hotel);
            $employee->setHotelAdmin(false);
            $employee->setMustChangePassword(true);
            foreach ($data->getRoles() as $role) {
                $role->addEmployee($employee);
            }

            $this->employeeRepository->save($employee);

            $emailSent = $this->sendWelcomeEmail($user, $employee, $data->getUsername(), $password);

            return $this->render('@CitadelAureum/core/staff/created.html.twig', [
                'employee' => $employee,
                'username' => $data->getUsername(),
                'password' => $password,
                'emailSent' => $emailSent,
            ]);
        }

        return $this->render('@CitadelAureum/core/staff/create.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/offboard', name: 'offboard', methods: ['GET'])]
    public function confirmOffboard(Employee $employee): Response
    {
        $this->denyUnlessOffboardable($employee);

        return $this->render('@CitadelAureum/core/staff/offboard.html.twig', ['employee' => $employee]);
    }

    #[Route('/{id}/offboard', name: 'offboard_confirm', methods: ['POST'])]
    public function offboard(Request $request, Employee $employee): Response
    {
        $this->denyUnlessOffboardable($employee);

        $token = (string)$request->request->get('_token');
        if (!$this->isCsrfTokenValid('aureum_staff_offboard_' . $employee->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $employee->getUser();
        if ($user !== null) {
            $this->entityManager->initializeObject($user);
        }

        $employee->archive();
        $this->employeeRepository->save($employee, false);

        if ($user !== null) {
            $this->userRepository->remove($user, false);
        }

        $this->employeeRepository->flush();

        $this->addFlash('success', 'Employee offboarded. Their history has been kept.');

        return $this->redirectToRoute('aureum_staff_list');
    }

    private function sendWelcomeEmail(User $user, Employee $employee, string $username, string $password): bool
    {
        $email = (new TemplatedEmail())
            ->subject('Your concierge desk account')
            ->htmlTemplate('@CitadelAureum/emails/staff_welcome.html.twig')
            ->context([
                'employee' => $employee,
                'hotel' => $employee->getHotel(),
                'username' => $username,
                'password' => $password,
            ]);

        try {
            $this->mailer->send($email, $user);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send staff welcome email', ['exception' => $e]);

            return false;
        }

        return true;
    }

    private function denyUnlessOffboardable(Employee $employee): void
    {
        $current = $this->aureumService->getEmployee();

        if ($employee->isArchived()) {
            throw $this->createNotFoundException();
        }

        if ($current !== null && $employee->getId() === $current->getId()) {
            throw $this->createAccessDeniedException('You cannot offboard yourself.');
        }

        if ($employee->isHotelAdmin() && !$this->isGranted(AureumVoter::RBAC_MANAGE)) {
            throw $this->createAccessDeniedException('Only a hotel admin can offboard another hotel admin.');
        }
    }
}
