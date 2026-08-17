<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Booking;
use Citadel\Aureum\Core\Entity\BookingLog;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\BookingField;
use Citadel\Aureum\Core\Entity\Enum\LogAction;
use Citadel\Aureum\Core\Repository\BookingLogRepository;

class BookingLogService
{
    private const LABEL_TYPE = 'Booking Type';
    private const LABEL_STATUS = 'Status';
    private const LABEL_VENDOR = 'Supplier';

    public function __construct(
        private readonly BookingLogRepository $bookingLogRepository,
    ) {
    }

    public function logCreated(Booking $booking, Employee $employee): void
    {
        $log = $this->newLog($booking, $employee, LogAction::CREATED);
        $log->setChanges(array_filter(
            $this->snapshot($booking),
            static fn (?string $value) => $value !== null && $value !== '',
        ));

        $this->bookingLogRepository->save($log);
    }

    /**
     * @param array<string, string|null> $originalData
     */
    public function logUpdated(Booking $booking, array $originalData, Employee $employee): void
    {
        $changes = $this->detectChanges($booking, $originalData);
        if ($changes === []) {
            return;
        }

        $action = count($changes) === 1 && isset($changes[self::LABEL_STATUS])
            ? LogAction::STATUS_CHANGED
            : LogAction::UPDATED;

        $log = $this->newLog($booking, $employee, $action);
        $log->setChanges($changes);

        $this->bookingLogRepository->save($log);
    }

    /**
     * @return array<string, string|null>
     */
    public function captureCurrentState(Booking $booking): array
    {
        return $this->snapshot($booking);
    }

    /**
     * @param array<string, string|null> $originalData
     * @return array<string, array{old: string|null, new: string|null}>
     */
    private function detectChanges(Booking $booking, array $originalData): array
    {
        $changes = [];
        foreach ($this->snapshot($booking) as $label => $new) {
            $old = $originalData[$label] ?? null;
            if ($old === $new) {
                continue;
            }

            $changes[$label] = ['old' => $old, 'new' => $new];
        }

        return $changes;
    }

    /**
     * @return array<string, string|null>
     */
    private function snapshot(Booking $booking): array
    {
        $snapshot = [
            self::LABEL_TYPE => $booking->getType()->getLabel(),
            'Date' => $booking->hasDate() ? $booking->getDate()->format('d/m/Y H:i') : null,
            'Guest Name' => $this->scalar($booking->getGuest()),
            'Guest Number' => $this->scalar($booking->getNumber()),
            'Guest Email' => $this->scalar($booking->getEmail()),
            'Concierge' => $booking->hasMiddleman() ? $booking->getMiddleman()->getName() : null,
            self::LABEL_VENDOR => $this->scalar($booking->getVendor()),
            'Reference' => $this->scalar($booking->getReference()),
            'Cost' => $this->scalar($booking->getCost()),
            'Notes' => $this->scalar($booking->getNotes()),
            self::LABEL_STATUS => $booking->getStatus()->getLabel(),
        ];

        foreach (BookingField::cases() as $field) {
            $snapshot[$field->getLabel()] = $this->scalar($booking->getDetail($field));
        }

        return $snapshot;
    }

    private function scalar(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    private function newLog(Booking $booking, Employee $employee, LogAction $action): BookingLog
    {
        $log = new BookingLog();
        $log->setBooking($booking);
        $log->setAction($action);
        $log->setPerformedBy($employee);
        $log->setHotel($booking->getHotel());

        return $log;
    }
}
