<?php

namespace Citadel\Aureum\Core\Service;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\LogAction;
use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Entity\LostPropertyLog;
use Citadel\Aureum\Core\Repository\LostPropertyLogRepository;
class LostPropertyLogService
{
    public function __construct(
        private LostPropertyLogRepository $logRepository,
    ){
    }

    public function logCreated(LostProperty $lostProperty, Employee $employee): void
    {
        $log = new LostPropertyLog();
        $log->setLostProperty($lostProperty);
        $log->setAction(LogAction::CREATED);
        $log->setPerformedBy($employee);
        $log->setHotel($employee->getHotel());

        $log->setChanges([
            'type' => $lostProperty->getType()->value,
            'description' => $lostProperty->getDescription(),
            'location' => $lostProperty->getLocation(),
            'status' => $lostProperty->getStatus()->value,
            'guest' => $lostProperty->getGuest(),
            'contact' => $lostProperty->getContact(),
        ]);

        $this->logRepository->save($log);
    }

    public function logUpdated(LostProperty $lostProperty, array $originalData, Employee $employee): void
    {
        $changes = $this->detectChanges($lostProperty, $originalData);

        if(empty($changes)) {
            return;
        }

        $log = new LostPropertyLog();
        $log->setLostProperty($lostProperty);
        $log->setPerformedBy($employee);
        $log->setHotel($employee->getHotel());

        if(isset($changes['status']) && count($changes) === 1) {
            $log->setAction(LogAction::STATUS_CHANGED);
        } else {
            $log->setAction(LogAction::UPDATED);
        }

        $log->setChanges($changes);
        $this->logRepository->save($log);
    }

    private function detectChanges(LostProperty $new, array $originalData): array
    {
        $changes =[];

        if ($originalData['type'] !== $new->getType()->value) {
            $changes['type'] = [
                'old' => $originalData['type'],
                'new' => $new->getType()->value,
            ];
        }

         if ($originalData['description'] !== $new->getDescription()) {
             $changes['description'] = [
                 'old' => $originalData['description'],
                 'new' => $new->getDescription(),
             ];
         }

         if ($originalData['location'] !== $new->getLocation()) {
             $changes['location'] = [
                 'old' => $originalData['location'],
                 'new' => $new->getLocation(),
             ];
         }

         if ($originalData['storedLocation'] !== $new->getStoredLocation()) {
             $changes['storedLocation'] = [
                 'old' => $originalData['storedLocation'],
                 'new' => $new->getStoredLocation(),
             ];
         }

         if ($originalData['status'] !== $new->getStatus()->value) {
             $changes['status'] = [
                 'old' => $originalData['status'],
                 'new' => $new->getStatus()->value,
             ];
         }

         if ($originalData['guest'] !== $new->getGuest()) {
             $changes['guest'] = [
                 'old' => $originalData['guest'],
                 'new' => $new->getGuest(),
             ];
         }

         if ($originalData['contact'] !== $new->getContact()) {
             $changes['contact'] = [
                 'old' => $originalData['contact'],
                 'new' => $new->getContact(),
             ];
         }

         if ($originalData['note'] !== $new->getNote()) {
             $changes['note'] = [
                 'old' => $originalData['note'],
                 'new' => $new->getNote(),
             ];
         }
         return $changes;
    }

    public function captureCurrentState(LostProperty $lostProperty): array
    {
        return [
            'type' => $lostProperty->getType()->value,
            'description' => $lostProperty->getDescription(),
            'location' => $lostProperty->getLocation(),
            'storedLocation' => $lostProperty->getStoredLocation(),
            'status' => $lostProperty->getStatus()->value,
            'reportedById' => $lostProperty->getReportedBy()->getId(),
            'reportedByName' => $lostProperty->getReportedBy()->getName(),
            'guest' => $lostProperty->getGuest(),
            'contact' => $lostProperty->getContact(),
            'note' => $lostProperty->getNote()
        ];
    }
}