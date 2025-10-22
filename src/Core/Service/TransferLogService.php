<?php

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\LogAction;
use Citadel\Aureum\Core\Entity\Transfer;
use Citadel\Aureum\Core\Entity\TransferLog;
use Citadel\Aureum\Core\Repository\TransferLogRepository;
class TransferLogService
{
    public function __construct(
        private readonly TransferLogRepository $transferLogRepository
    ){
    }

    public function logCreated(Transfer $transfer, Employee $employee): void
    {
        $log = new TransferLog();
        $log->setTransfer($transfer);
        $log->setAction(LogAction::CREATED);
        $log->setPerformedBy($employee);
        $log->setHotel($employee->getHotel());

        $log->setChanges([
            'date' => $transfer->getDate(),
            'guest' => $transfer->getGuest(),
            'number' => $transfer->getNumber(),
            'email' => $transfer->getEmail(),
            'pickup' => $transfer->getPickup(),
            'dropoff' => $transfer->getDropoff(),
            'middleman' => $transfer->getMiddleman(),
            'driver' => $transfer->getDriver(),
            'cost' => $transfer->getCost(),
            'notes' => $transfer->getNotes(),
            'status' => $transfer->getStatus()->getLabel(),
        ]);

        $this->transferLogRepository->save($log);
    }

    public function logUpdated(Transfer $transfer, array $originalData, Employee $employee): void
    {
        $changes = $this->detectChanges($transfer, $originalData);

        if(empty($changes)) {
            return;
        }

        $log = new TransferLog();
        $log->setTransfer($transfer);
        $log->setPerformedBy($employee);
        $log->setHotel($employee->getHotel());

        if(isset($changes['status']) && count($changes) === 1) {
            $log->setAction(LogAction::STATUS_CHANGED);
        } else {
            $log->setAction(LogAction::UPDATED);
        }

        $log->setChanges($changes);
        $this->transferLogRepository->save($log);
    }

    private function detectChanges(Transfer $new, array $originalData): array
    {
        $changes = [];

        if($originalData['date'] !== $new->getDate()) {
            $changes['date'] = [
                'old' => $originalData['date'],
                'new' => $new->getDate(),
            ];
        }

        if($originalData['guest'] !== $new->getGuest()) {
            $changes['guest'] = [
                'old' => $originalData['guest'],
                'new' => $new->getGuest(),
            ];
        }

        if($originalData['number'] !== $new->getNumber()) {
            $changes['number'] = [
                'old' => $originalData['number'],
                'new' => $new->getNumber(),
            ];
        }

        if($originalData['email'] !== $new->getEmail()) {
            $changes['email'] = [
                'old' => $originalData['email'],
                'new' => $new->getEmail(),
            ];
        }

        if($originalData['pickup'] !== $new->getPickup()) {
            $changes['pickup'] = [
                'old' => $originalData['pickup'],
                'new' => $new->getPickup(),
            ];
        }

        if($originalData['dropoff'] !== $new->getDropoff()) {
            $changes['dropoff'] = [
                'old' => $originalData['dropoff'],
                'new' => $new->getDropoff(),
            ];
        }

        if($originalData['middleman'] !==$new->getMiddleman()) {
            $changes['middleman'] = [
                'old' => $originalData['middleman'],
                'new' => $new->getMiddleman(),
            ];
        }

        if($originalData['driver'] !== $new->getDriver()) {
            $changes['driver'] = [
                'old' => $originalData['driver'],
                'new' => $new->getDriver(),
            ];
        }

        if($originalData['cost'] !== $new->getCost()) {
            $changes['cost'] = [
                'old' => $originalData['cost'],
                'new' => $new->getCost(),
            ];
        }

        if($originalData['notes'] !== $new->getNotes()) {
            $changes['notes'] = [
                'old' => $originalData['notes'],
                'new' => $new->getNotes(),
            ];
        }

        if($originalData['status'] !== $new->getStatus()->getLabel()) {
            $changes['status'] = [
                'old' => $originalData['status'],
                'new' => $new->getStatus()->getLabel(),
            ];
        }

        return $changes;
    }

    public function captureCurrentState(Transfer $transfer): array
    {
        return [
            'date' => $transfer->getDate(),
            'guest' => $transfer->getGuest(),
            'number' => $transfer->getNumber(),
            'email' => $transfer->getEmail(),
            'pickup' => $transfer->getPickup(),
            'dropoff' => $transfer->getDropoff(),
            'middleman' => $transfer->getMiddleman(),
            'driver' => $transfer->getDriver(),
            'cost' => $transfer->getCost(),
            'notes' => $transfer->getNotes(),
            'status' => $transfer->getStatus()->getLabel(),
        ];
    }
}