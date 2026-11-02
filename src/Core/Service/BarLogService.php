<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Entity\BarLog;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\LogAction;
use Citadel\Aureum\Core\Repository\BarLogRepository;

class BarLogService
{
    public function __construct(
        private readonly BarLogRepository $barLogRepository,
    ) {
    }

    public function logCreated(Bar $bar, Employee $employee): void
    {
        $this->save($bar, $employee, LogAction::CREATED, array_filter(
            $this->captureCurrentState($bar),
            static fn ($value) => $value !== null && $value !== [],
        ));
    }

    public function logUpdated(Bar $bar, array $originalData, Employee $employee): void
    {
        $changes = [];
        foreach ($this->captureCurrentState($bar) as $field => $value) {
            $old = $originalData[$field] ?? null;
            if ($old !== $value) {
                $changes[$field] = ['old' => $old, 'new' => $value];
            }
        }

        if ($changes === []) {
            return;
        }

        $this->save($bar, $employee, LogAction::UPDATED, $changes);
    }

    public function captureCurrentState(Bar $bar): array
    {
        return [
            'name' => $bar->getName(),
            'neighbourhood' => $bar->getNeighbourhood(),
            'street' => $bar->getStreet(),
            'styles' => $bar->getStyles()->map(fn ($style) => $style->getName())->getValues(),
            'dressCode' => $bar->getDressCode()?->getLabel(),
            'entryPolicy' => $bar->getEntryPolicy()?->getLabel(),
            'happyHour' => $bar->getHappyHour(),
            'tags' => $bar->getTags()->map(fn ($tag) => $tag->getName())->getValues(),
            'connections' => $bar->getConnections()->map(fn ($employee) => $employee->getName())->getValues(),
        ];
    }

    private function save(Bar $bar, Employee $employee, LogAction $action, array $changes): void
    {
        $log = new BarLog();
        $log->setBar($bar);
        $log->setAction($action);
        $log->setPerformedBy($employee);
        $log->setHotel($bar->getHotel());
        $log->setChanges($changes);

        $this->barLogRepository->save($log);
    }
}
