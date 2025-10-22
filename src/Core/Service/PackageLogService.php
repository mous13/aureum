<?php

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\LogAction;
use Citadel\Aureum\Core\Entity\Package;
use Citadel\Aureum\Core\Entity\PackageLog;
use Citadel\Aureum\Core\Repository\PackageLogRepository;

class PackageLogService
{
    public function __construct(
        private PackageLogRepository $packageLogRepository,
    ){
    }

    public function logCreated(Package $package, Employee $employee): void
    {
        $log = new PackageLog();
        $log->setPackage($package);
        $log->setAction(LogAction::CREATED);
        $log->setPerformedBy($employee);
        $log->setHotel($package->getHotel());

        $log->setChanges([
            'name' => $package->getName(),
            'description' => $package->getDescription(),
            'location' => $package->getLocation(),
            'note' => $package->getNote(),
            'status' => $package->getStatus()->getLabel(),
        ]);

        $this->packageLogRepository->save($log);
    }

    public function logUpdated(Package $package, array $originalData, Employee $employee): void
    {
        $changes = $this->detectChanges($package, $originalData);

        if(empty($changes)) {
            return;
        }

        $log = new PackageLog();
        $log->setPackage($package);
        $log->setPerformedBy($employee);
        $log->setHotel($package->getHotel());

        if(isset($changes['status']) && count($changes) === 1){
            $log->setAction(LogAction::STATUS_CHANGED);
        } else {
            $log->setAction(LogAction::UPDATED);
        }

        $log->setChanges($changes);
        $this->packageLogRepository->save($log);
    }

    private function detectChanges(Package $new, array $originalData): array
    {
        $changes = [];

        if($originalData['name'] !== $new->getName()) {
            $changes['name'] = [
                'old' => $originalData['name'],
                'new' => $new->getName(),
            ];
        }

        if($originalData['description'] !== $new->getDescription()) {
            $changes['description'] = [
                'old' => $originalData['description'],
                'new' => $new->getDescription(),
            ];
        }

        if($originalData['location'] !== $new->getLocation()) {
            $changes['location'] = [
                'old' => $originalData['location'],
                'new' => $new->getLocation(),
            ];
        }

        if($originalData['note'] !== $new->getNote()) {
            $changes['note'] = [
                'old' => $originalData['note'],
                'new' => $new->getNote(),
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

    public function captureCurrentState(Package $package): array
    {
        return [
            'name' => $package->getName(),
            'description' => $package->getDescription(),
            'location' => $package->getLocation(),
            'note' => $package->getNote(),
            'status' => $package->getStatus()->getLabel(),
        ];
    }
}