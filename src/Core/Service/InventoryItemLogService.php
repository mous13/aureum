<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\LogAction;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\InventoryItemLog;
use Citadel\Aureum\Core\Repository\InventoryItemLogRepository;

class InventoryItemLogService
{
    public function __construct(
        private readonly InventoryItemLogRepository $logRepository,
    ) {
    }

    public function logCreated(InventoryItem $item, Employee $employee): void
    {
        $log = new InventoryItemLog();
        $log->setItem($item);
        $log->setAction(LogAction::CREATED);
        $log->setPerformedBy($employee);
        $log->setHotel($employee->getHotel());
        $log->setChanges($this->captureCurrentState($item));

        $this->logRepository->save($log);
    }

    /**
     * @param array<string, mixed> $originalData
     */
    public function logUpdated(InventoryItem $item, array $originalData, Employee $employee): void
    {
        $changes = $this->detectChanges($item, $originalData);
        if ($changes === []) {
            return;
        }

        $log = new InventoryItemLog();
        $log->setItem($item);
        $log->setAction(LogAction::UPDATED);
        $log->setPerformedBy($employee);
        $log->setHotel($employee->getHotel());
        $log->setChanges($changes);

        $this->logRepository->save($log);
    }

    /**
     * @return array<string, mixed>
     */
    public function captureCurrentState(InventoryItem $item): array
    {
        return [
            'name' => $item->getName(),
            'unit' => $item->getUnit(),
            'packSize' => $item->getPackSize(),
            'packLabel' => $item->getPackLabel(),
            'leadTimeDays' => $item->getLeadTimeDays(),
            'safetyBufferDays' => $item->getSafetyBufferDays(),
            'active' => $item->isActive(),
        ];
    }

    /**
     * @param array<string, mixed> $originalData
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function detectChanges(InventoryItem $item, array $originalData): array
    {
        $changes = [];

        foreach ($this->captureCurrentState($item) as $field => $value) {
            if (($originalData[$field] ?? null) !== $value) {
                $changes[$field] = [
                    'old' => $originalData[$field] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
