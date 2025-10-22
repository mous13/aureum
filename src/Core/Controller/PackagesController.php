<?php

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Package;
use Citadel\Aureum\Core\Entity\PackageStatus;
use Citadel\Aureum\Core\Form\PackageEditType;
use Citadel\Aureum\Core\Form\PackageType;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\PackageLogRepository;
use Citadel\Aureum\Core\Repository\PackageRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\PackageLogService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;


class PackagesController extends AbstractController
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
        private readonly Security $security,
        private readonly EmployeeRepository $employeeRepository,
        private readonly PackageLogService $logService,
        private readonly PackageLogRepository $packageLogRepository,
        private readonly AureumService $aureumService,
    ){
    }

    #[Route('/packages', name: 'packages')]
    public function index(Request $request): Response
    {
        $user = $this->security->getUser();
        $employee = $this->employeeRepository->findOneBy(['user' => $user]);
        $hotel = $employee->getHotel();
        $packages = $this->packageRepository->findAllOrderedByDate($hotel);

        $package = new Package();
        $form = $this->createForm(PackageType::class, $package);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $package = $form->getData();

            $package->setStatus(PackageStatus::RECEIVED);
            $package->setEmployee($employee);
            $package->setHotel($hotel);
            $this->packageRepository->save($package);

            $this->logService->logCreated($package, $employee);

            return $this->redirectToRoute('aureum_packages');
        }

        $editForms = [];
        foreach ($packages as $pkg) {
            $editForm = $this->createForm(PackageEditType::class, $pkg, [
                'action' => $this->generateUrl('aureum_packages_edit', ['id' => $pkg->getId()])
            ]);
            $editForms[$pkg->getId()] = $editForm->createView();
        }

        $packageLogs = [];
        foreach($packages as $pkg) {
            $packageLogs[$pkg->getId()] = $this->packageLogRepository->findByPackage($pkg);
        }

        return $this->render('@CitadelAureum/core/packages/packages.html.twig',[
            'packages' => $packages,
            'form' => $form,
            'editForms' => $editForms,
            'logs' => $packageLogs,
        ]);
    }

    #[Route('/packages/{id}/edit', name: 'packages_edit')]
    public function edit(Request $request, Package $package): Response
    {
        $originalData = $this->logService->captureCurrentState($package);
        $employee = $this->aureumService->getEmployee();

        $form = $this->createForm(PackageEditType::class, $package);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if($form->get('status')->getData() === true) {
                $package->setStatus(PackageStatus::PICKED_UP);
            }

            $package->setUpdatedAt(new \DateTime());

            $this->packageRepository->save($package);

            $this->logService->logUpdated($package, $originalData, $employee);

            $this->addFlash('success', 'Package collected');
        }
        return $this->redirectToRoute('aureum_packages');
    }
}