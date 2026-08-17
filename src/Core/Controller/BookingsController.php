<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Booking;
use Citadel\Aureum\Core\Entity\Enum\BookingType;
use Citadel\Aureum\Core\Form\BookingEditType;
use Citadel\Aureum\Core\Form\BookingFormType;
use Citadel\Aureum\Core\Repository\BookingRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\BookingLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.bookings.view')]
class BookingsController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly BookingLogService $logService,
        private readonly AureumService $aureumService,
    ) {
    }

    #[Route('/bookings', name: 'bookings')]
    public function index(Request $request): Response
    {
        $employee = $this->aureumService->getEmployee();
        $hotel = $this->aureumService->getHotel();

        $booking = new Booking();
        $form = $this->createForm(BookingFormType::class, $booking, [
            'userTimezone' => $employee->getUser()->getTimezone(),
            'hotel' => $hotel,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('aureum.module.bookings.manage');

            $booking->setHotel($hotel);
            $this->bookingRepository->save($booking);
            $this->logService->logCreated($booking, $employee);

            $this->addFlash('success', 'Booking created');

            return $this->redirectToRoute('aureum_bookings');
        }

        return $this->render('@CitadelAureum/core/bookings/bookings.html.twig', [
            'form' => $form,
            'hotelId' => $hotel->getId(),
            'types' => BookingType::cases(),
            'typeMeta' => BookingType::detailMeta(),
        ]);
    }

    #[Route('/bookings/{id}/edit', name: 'bookings_edit')]
    #[IsGranted('aureum.module.bookings.manage')]
    public function edit(Request $request, Booking $booking): Response
    {
        $employee = $this->aureumService->getEmployee();
        if ($booking->getHotel() !== $employee->getHotel()) {
            throw $this->createNotFoundException();
        }

        $originalData = $this->logService->captureCurrentState($booking);

        $form = $this->createForm(BookingEditType::class, $booking, [
            'userTimezone' => $employee->getUser()->getTimezone(),
            'hotel' => $booking->getHotel(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->bookingRepository->save($booking);
            $this->logService->logUpdated($booking, $originalData, $employee);

            $this->addFlash('success', 'Booking updated');
        }

        return $this->redirectToRoute('aureum_bookings');
    }
}
