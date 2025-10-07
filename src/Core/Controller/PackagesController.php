<?php

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Package;
use Citadel\Aureum\Core\Form\PackageEditType;
use Citadel\Aureum\Core\Form\PackageType;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\PackageRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;


class PackagesController extends AbstractController
{
    public function __construct(
        readonly PackageRepository $packageRepository,
        readonly Security $security,
        readonly EmployeeRepository $employeeRepository,
    ){
    }

    #[Route('/packages', name: 'packages')]
    public function index(Request $request): Response
    {
        $user = $this->security->getUser();
        $employee = $this->employeeRepository->findOneBy(['user' => $user]);
        $hotel = $employee->getHotel();
        $packages = $this->packageRepository->findBy(['hotel' => $hotel]);

        $package = new Package();
        $form = $this->createForm(PackageType::class, $package);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $package = $form->getData();

            $package->setHotel($hotel);
            $this->packageRepository->save($package);

            return $this->redirectToRoute('aureum_packages');
        }

        $editForms = [];
        foreach ($packages as $pkg) {
            $editForm = $this->createForm(PackageEditType::class, $pkg, [
                'action' => $this->generateUrl('aureum_packages_edit', ['id' => $pkg->getId()])
            ]);
            $editForms[$pkg->getId()] = $editForm->createView();
        }

        return $this->render('@CitadelAureum/core/packages/packages.html.twig',[
            'packages' => $packages,
            'form' => $form,
            'editForms' => $editForms,
        ]);
    }

    #[Route('/packages/{id}/edit', name: 'packages_edit')]
    public function edit(Request $request, Package $package): Response
    {
        $form = $this->createForm(PackageEditType::class, $package);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $package->setStatus(!$form->get('status')->getData());
            $package->setUpdatedAt(new \DateTime());

            $this->packageRepository->save($package);

            $this->addFlash('success', 'Package collected');
        }
        return $this->redirectToRoute('aureum_packages');
    }


}