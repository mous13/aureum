<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Booking;
use Citadel\Aureum\Core\Entity\Enum\BookingStatus;
use Citadel\Aureum\Core\Entity\Enum\BookingType;
use Citadel\Aureum\Core\Form\BookingEditType;
use Citadel\Aureum\Core\Repository\BookingLogRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

#[AsLiveComponent('Aureum\BookingTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.bookings.view')]
class BookingTable extends AbstractDoctrineTable
{
    /** @var array<string, "ASC"|"DESC"|null> */
    #[LiveProp]
    public array $sort = ['date' => self::SORT_DESC];

    #[LiveProp]
    public int $hotelId;

    /** @var array<string> */
    #[LiveProp]
    public array $statuses = [];

    public function __construct(
        private readonly BookingLogRepository $bookingLogRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FormFactoryInterface $formFactory,
        private readonly AureumService $aureumService,
        private readonly Environment $twig,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Booking::class;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        $typeSearch = trim((string)($search['type'] ?? ''));
        $guestSearch = trim((string)($search['guest'] ?? ''));
        unset($search['type'], $search['guest']);

        $qb = parent::getQuery($search)
            ->andWhere('e.hotel = :hotel')
            ->setParameter('hotel', $this->hotelId);

        if ($this->statuses !== []) {
            $qb
                ->andWhere('e.status IN (:statuses)')
                ->setParameter('statuses', array_map(
                    static fn (string $status) => BookingStatus::from($status),
                    $this->statuses,
                ));
        }

        if ($typeSearch !== '') {
            $types = $this->matchTypes($typeSearch);
            if ($types === []) {
                return $qb->andWhere('1 = 0');
            }

            $qb
                ->andWhere('e.type IN (:types)')
                ->setParameter('types', $types);
        }

        if ($guestSearch !== '') {
            $qb
                ->andWhere($qb->expr()->orX(
                    'LOWER(e.guest) LIKE LOWER(:guest)',
                    'LOWER(e.number) LIKE LOWER(:guest)',
                    'LOWER(e.email) LIKE LOWER(:guest)',
                ))
                ->setParameter('guest', '%' . $guestSearch . '%');
        }

        return $qb;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('type', [
                'label' => 'Type',
                'field' => 'type',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderType(...),
            ])
            ->addColumn('date', [
                'label' => 'Date',
                'field' => 'date',
                'sortable' => true,
                'searchable' => false,
                'renderer' => $this->renderDate(...),
            ])
            ->addColumn('guest', [
                'label' => 'Guest',
                'field' => 'guest',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderGuest(...),
            ])
            ->addColumn('details', [
                'label' => 'Details',
                'field' => 'id',
                'sortable' => false,
                'searchable' => false,
                'renderer' => $this->renderDetails(...),
            ])
            ->addColumn('vendor', [
                'label' => 'Supplier',
                'field' => 'vendor',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderVendor(...),
            ])
            ->addColumn('concierge', [
                'label' => 'Concierge',
                'field' => 'middleman.name',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ]);

        if ($this->security->isGranted('aureum.module.bookings.manage')) {
            $this->addColumn('cost', [
                'label' => 'Cost',
                'field' => 'cost',
                'sortable' => false,
                'searchable' => false,
                'renderer' => $this->renderText(...),
            ]);
        }

        $this
            ->addColumn('status', [
                'label' => 'Status',
                'field' => 'status',
                'sortable' => true,
                'searchable' => false,
                'renderer' => $this->renderStatus(...),
            ])
            ->addColumn('actions', [
                'label' => '',
                'field' => 'id',
                'sortable' => false,
                'searchable' => false,
                'renderer' => $this->renderActions(...),
            ]);
    }

    /** @return array<BookingType> */
    private function matchTypes(string $term): array
    {
        $term = mb_strtolower($term);

        return array_values(array_filter(
            BookingType::cases(),
            static fn (BookingType $type) => str_contains(mb_strtolower($type->getLabel()), $term)
                || str_contains($type->value, $term),
        ));
    }

    private function renderText(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES);
    }

    private function renderType(BookingType $type): string
    {
        return sprintf(
            '<i class="%s"></i> %s',
            htmlspecialchars($type->getIcon(), ENT_QUOTES),
            htmlspecialchars($type->getLabel(), ENT_QUOTES),
        );
    }

    private function renderDate(?\DateTimeInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        return \DateTimeImmutable::createFromInterface($date)
            ->setTimezone(new \DateTimeZone($this->getUserTimezone()))
            ->format('d/m/y H:i');
    }

    private function getUserTimezone(): string
    {
        return $this->aureumService->getEmployee()?->getUser()?->getTimezone() ?? 'UTC';
    }

    private function renderGuest(mixed $guest, Booking $booking): string
    {
        $lines = [sprintf('<div>%s</div>', $this->renderText($guest))];
        foreach ([$booking->getNumber(), $booking->getEmail()] as $contact) {
            if ($contact !== null && $contact !== '') {
                $lines[] = sprintf('<div class="text-small">%s</div>', $this->renderText($contact));
            }
        }

        return implode('', $lines);
    }

    private function renderDetails(mixed $id, Booking $booking): string
    {
        $summary = $booking->getSummary();
        if ($summary === []) {
            return '<span class="text-small">&mdash;</span>';
        }

        return implode('', array_map(
            fn (array $detail) => sprintf(
                '<div class="text-small"><span class="text-bold">%s:</span> %s</div>',
                $this->renderText($detail['label']),
                $this->renderText($detail['value']),
            ),
            $summary,
        ));
    }

    private function renderVendor(mixed $vendor, Booking $booking): string
    {
        $rendered = sprintf('<div>%s</div>', $this->renderText($vendor));
        if ($booking->getReference() !== null) {
            $rendered .= sprintf('<div class="text-small">Ref: %s</div>', $this->renderText($booking->getReference()));
        }

        return $rendered;
    }

    private function renderStatus(BookingStatus $status, Booking $booking): string
    {
        $rendered = htmlspecialchars($status->getLabel(), ENT_QUOTES);
        if ($booking->isOverdue()) {
            $rendered .= ' <i class="text-huge ph ph-clock-countdown" style="color:red;" title="OVERDUE"></i>';
        }

        return $rendered;
    }

    private function renderActions(mixed $id, Booking $booking): string
    {
        $actions = $this->twig->render('@CitadelAureum/core/bookings/blocks/booking_notes.html.twig', [
            'booking' => $booking,
            'logs' => $this->bookingLogRepository->findByBooking($booking),
            'userTimezone' => $this->getUserTimezone(),
        ]);

        if (!$this->security->isGranted('aureum.module.bookings.manage')) {
            return $actions;
        }

        $editForm = $this->formFactory->create(BookingEditType::class, $booking, [
            'action' => $this->urlGenerator->generate('aureum_bookings_edit', ['id' => $id]),
            'userTimezone' => $this->aureumService->getEmployee()?->getUser()?->getTimezone() ?? 'UTC',
            'hotel' => $booking->getHotel(),
        ]);

        return $actions . $this->twig->render('@CitadelAureum/core/bookings/blocks/bookings_edit.html.twig', [
            'booking' => $booking,
            'editForm' => $editForm->createView(),
            'typeMeta' => BookingType::detailMeta(),
        ]);
    }
}
