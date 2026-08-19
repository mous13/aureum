<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Controller;

use Citadel\Aureum\Admin\Form\DTO\NewEmployee;
use Citadel\Aureum\Admin\Form\EmployeeEditType;
use Citadel\Aureum\Admin\Form\EmployeeType;
use Citadel\Aureum\Admin\Service\CreateEmployeeService;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Exception\UserAlreadyExistsException;
use Forumify\Core\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/employees', 'employees')]
#[IsGranted('aureum.admin.employees.manage')]
class EmployeeController extends AbstractController
{
    public function __construct(
        private readonly HotelRepository $hotelRepository,
        private readonly CreateEmployeeService $createEmployeeService,
        private readonly EmployeeRepository $employeeRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('', '_list')]
    public function list(): Response
    {
        return $this->render('@CitadelAureum/admin/employee/list.html.twig', [
            'table' => 'EmployeeTable',
            'translationPrefix' => 'aureum.admin.employee.crud.',
            'route' => 'aureum_admin_employees',
            'capabilities' => [
                'create' => true,
                'edit' => true,
                'delete' => true,
            ],
        ]);
    }

    #[Route('/hotel/{hotelId}', '_list_by_hotel')]
    public function listByHotel(int $hotelId): Response
    {

        $hotel = $this->hotelRepository->find($hotelId);



        return $this->render('@CitadelAureum/admin/employee/list.html.twig', [
            'table' => 'EmployeeTable',
            'hotel' => $hotel,
            'hotelId' => $hotelId,
            'translationPrefix' => 'aureum.admin.employee.crud.',
            'route' => 'aureum_admin_employees',
            'hotelSpecific' => true,
            'capabilities' => [
                'create' => true,
                'edit' => true,
                'delete' => true,
            ],
        ]);
    }

    #[Route('/create', '_create')]
    public function create(Request $request): Response
    {
        $newEmployee = new NewEmployee();

        return $this->handleEmployeeForm($request, $newEmployee, 'aureum_admin_employees_list');
    }



    #[Route('/hotel/{hotelId}/create', '_create_for_hotel')]
    public function createForHotel(Request $request, int $hotelId): Response
    {
        try {
            $hotel = $this->hotelRepository->findByIdOrFail($hotelId);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException('Hotel not found');
        }

        // Set hotel in request attributes so the form can pick it up
        $request->attributes->set('hotelId', $hotelId);

        $newEmployee = new NewEmployee();
        $newEmployee->setHotel($hotel);

        return $this->handleEmployeeForm(
            $request,
            $newEmployee,
            'aureum_admin_employees_list_by_hotel',
            ['hotelId' => $hotelId]
        );
    }

    private function handleEmployeeForm(
        Request $request,
        NewEmployee $newEmployee,
        string $redirectRoute,
        array $redirectParams = []
    ): Response {
        $form = $this->createForm(EmployeeType::class, $newEmployee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->createEmployeeService->createEmployee($newEmployee);

                $this->addFlash('success', 'Employee created successfully.');

                return $this->redirectToRoute($redirectRoute, $redirectParams);
            } catch (UserAlreadyExistsException) {
                $this->addFlash('error', 'Username or email already exists. Please choose different values.');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while creating the employee. Please try again.');
                $this->logger->error('Employee creation failed', ['exception' => $e]);
            }
        }

        $templateData = [
            'form' => $form,
            'translationPrefix' => 'aureum.admin.employee.crud.',
            'route' => 'aureum_admin_employees',
            'capabilities' => [
                'create' => true,
                'edit' => true,
                'delete' => true,
            ],
        ];

        if ($newEmployee->getHotel() !== null) {
            $templateData['hotel'] = $newEmployee->getHotel();
            $templateData['hotelId'] = $newEmployee->getHotel()->getId();
            $templateData['hotelSpecific'] = true;
        }

        return $this->render('@CitadelAureum/admin/employee/form.html.twig', $templateData);
    }

    #[Route('/edit/{id}', '_edit')]
    public function edit(Request $request, int $id): Response
    {
        $employee = $this->findEmployeeOr404($id);

        $form = $this->createForm(EmployeeEditType::class, $employee);
        $form->get('verified')->setData($employee->getUser()?->isEmailVerified() ?? false);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $isVerified = (bool)$form->get('verified')->getData();

            $user = $employee->getUser();
            if ($user !== null) {
                $this->entityManager->initializeObject($user);
                $user->setEmailVerified($isVerified);
                $this->userRepository->save($user);
            }

            $this->employeeRepository->save($data);

            $this->addFlash('success', 'Employee updated successfully.');
            return $this->redirectToRoute('aureum_admin_hotels_list');
        }

        return $this->render('@CitadelAureum/admin/employee/form.html.twig', [
            'form' => $form,
            'employee' => $employee,
        ]);
    }

    #[Route('/delete/{id}', '_delete', methods: ['GET'])]
    public function confirmDelete(int $id): Response
    {
        return $this->render('@CitadelAureum/admin/employee/delete.html.twig', [
            'employee' => $this->findEmployeeOr404($id),
        ]);
    }

    #[Route('/delete/{id}', '_delete_confirm', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $employee = $this->findEmployeeOr404($id);

        if (!$this->isCsrfTokenValid('aureum_employee_delete_' . $id, (string)$request->request->get('_token'))) {
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

        $this->addFlash('success', 'Employee offboarded. Their history has been kept for the audit trail.');

        return $this->redirectToRoute('aureum_admin_hotels_list');
    }

    private function findEmployeeOr404(int $id): Employee
    {
        $employee = $this->employeeRepository->find($id);
        if ($employee === null || $employee->isArchived()) {
            throw $this->createNotFoundException();
        }

        return $employee;
    }
}
