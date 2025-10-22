<?php

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\LogAction;
use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\FineLog;
use Citadel\Aureum\Core\Repository\FineLogRepository;

class FineLogService
{
    public function __construct(
        private readonly FineLogRepository $fineLogRepository,
    ){
    }

    public function logCreated(Fine $fine, Employee $employee): void
    {
        $log = new FineLog();
        $log->setFine($fine);
        $log->setAction(LogAction::CREATED);
        $log->setPerformedBy($employee);
        $log->setHotel($employee->getHotel());

        $log->setChanges([
            'number' => $fine->getNumber(),
            'name' => $fine->getName(),
            'email' => $fine->getEmail(),
            'note' => $fine->getNote(),
            'status' => $fine->getStatus()->value,
        ]);

        $this->fineLogRepository->save($log);
    }

    public function logUpdated(Fine $fine, array $originalData, Employee $employee): void
    {
        $changes = $this->detectChanges($fine, $originalData);

        if(empty($changes)) {
            return;
        }

        $log = new FineLog();
        $log->setFine($fine);
        $log->setPerformedBy($employee);
        $log->setHotel($employee->getHotel());

        if(isset($changes['status']) && count($changes) === 1){
            $log->setAction(LogAction::STATUS_CHANGED);
        } else {
            $log->setAction(LogAction::UPDATED);
        }

        $log->setChanges($changes);
        $this->fineLogRepository->save($log);
    }

    private function detectChanges(Fine $new, array $originalData): array
    {
        $changes = [];

        if($originalData['number'] !== $new->getNumber()) {
            $changes['number'] = [
                'old' => $originalData['number'],
                'new' => $new->getNumber(),
            ];
        }

        if($originalData['name'] !== $new->getName()) {
            $changes['name'] = [
                'old' => $originalData['name'],
                'new' => $new->getName(),
            ];
        }

        if($originalData['email'] !== $new->getEmail()) {
            $changes['email'] = [
                'old' => $originalData['email'],
                'new' => $new->getEmail(),
            ];
        }

        if($originalData['note'] !== $new->getNote()) {
            $changes['note'] = [
                'old' => $originalData['note'],
                'new' => $new->getNote(),
            ];
        }

        if($originalData['status'] !== $new->getStatus()->value) {
            $changes['status'] = [
                'old' => $originalData['status'],
                'new' => $new->getStatus()->value,
            ];
        }

        return $changes;
    }

    public function captureCurrentState(Fine $fine): array
    {
        return [
            'number' => $fine->getNumber(),
            'name' => $fine->getName(),
            'email' => $fine->getEmail(),
            'note' => $fine->getNote(),
            'status' => $fine->getStatus()->value,
        ];
    }
}