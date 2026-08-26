<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\SopStanding;
use Citadel\Aureum\Core\Entity\HotelRole;
use Citadel\Aureum\Core\Entity\Sop;
use Citadel\Aureum\Core\Entity\SopSignOff;
use DateTimeImmutable;

class SopStandingService
{
    public function standingFor(
        Sop $sop,
        Employee $employee,
        DateTimeImmutable $now,
        ?SopSignOff $signOff,
    ): SopStanding {
        if (!$sop->getStatus()->isActionable() || !$this->inAudience($sop, $employee)) {
            return SopStanding::NOT_APPLICABLE;
        }

        if ($signOff === null || $signOff->getVersion() !== $sop->getVersion()) {
            return SopStanding::SIGN_OFF_NEEDED;
        }

        $recheckMonths = $sop->getRecheckMonths();
        if ($recheckMonths !== null) {
            $dueAt = DateTimeImmutable::createFromInterface($signOff->getSignedAt())
                ->modify("+{$recheckMonths} months");
            if ($dueAt <= $now) {
                return SopStanding::RECHECK_DUE;
            }
        }

        return SopStanding::CURRENT;
    }

    public function inAudience(Sop $sop, Employee $employee): bool
    {
        if ($sop->getAudience()->isEmpty()) {
            return true;
        }

        $audienceIds = $sop->getAudience()
            ->map(static fn (HotelRole $role) => $role->getId())
            ->getValues();

        foreach ($employee->getHotelRoles() as $role) {
            if (in_array($role->getId(), $audienceIds, true)) {
                return true;
            }
        }

        return false;
    }
}
