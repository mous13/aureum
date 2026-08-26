<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\Sop;
use Citadel\Aureum\Core\Entity\SopSignOff;
use DateTime;
use PHPUnit\Framework\TestCase;

class SopSignOffTest extends TestCase
{
    public function testNoRecheckIntervalMeansNoDueDate(): void
    {
        self::assertNull($this->signOff(null, '2026-01-15 09:00:00')->getRecheckDueAt());
    }

    public function testDueDateAddsWholeMonths(): void
    {
        $due = $this->signOff(6, '2026-02-26 12:00:00')->getRecheckDueAt();

        self::assertSame('2026-08-26 12:00:00', $due->format('Y-m-d H:i:s'));
    }

    public function testDueDateClampsToTheEndOfShorterMonths(): void
    {
        $due = $this->signOff(1, '2026-01-31 09:30:00')->getRecheckDueAt();

        self::assertSame('2026-02-28 09:30:00', $due->format('Y-m-d H:i:s'));
    }

    public function testDueDateClampsAcrossAYearBoundary(): void
    {
        $due = $this->signOff(6, '2026-08-31 07:00:00')->getRecheckDueAt();

        self::assertSame('2027-02-28 07:00:00', $due->format('Y-m-d H:i:s'));
    }

    private function signOff(?int $recheckMonths, string $signedAt): SopSignOff
    {
        $sop = new Sop();
        $sop->setRecheckMonths($recheckMonths);

        $signOff = new SopSignOff();
        $signOff->setSop($sop);
        $signOff->setSignedAt(new DateTime($signedAt));

        return $signOff;
    }
}
