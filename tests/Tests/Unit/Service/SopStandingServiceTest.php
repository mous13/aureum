<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\SopStanding;
use Citadel\Aureum\Core\Entity\Enum\SopStatus;
use Citadel\Aureum\Core\Entity\HotelRole;
use Citadel\Aureum\Core\Entity\Sop;
use Citadel\Aureum\Core\Entity\SopSignOff;
use Citadel\Aureum\Core\Service\SopStandingService;
use Citadel\Aureum\Tests\Unit\EntityIdTrait;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SopStandingServiceTest extends TestCase
{
    use EntityIdTrait;

    private SopStandingService $service;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->service = new SopStandingService();
        $this->now = new DateTimeImmutable('2026-08-26 12:00:00');
    }

    public function testAnEmptyAudienceAppliesToEveryone(): void
    {
        $sop = $this->publishedSop();

        self::assertSame(
            SopStanding::SIGN_OFF_NEEDED,
            $this->service->standingFor($sop, $this->employee(), $this->now, null),
        );
    }

    public function testAnEmployeeOutsideTheAudienceIsNotChased(): void
    {
        $sop = $this->publishedSop();
        $sop->getAudience()->add($this->role(1));

        $employee = $this->employee($this->role(2));

        self::assertSame(
            SopStanding::NOT_APPLICABLE,
            $this->service->standingFor($sop, $employee, $this->now, null),
        );
    }

    public function testAnEmployeeWithAMatchingRoleIsChased(): void
    {
        $role = $this->role(1);
        $sop = $this->publishedSop();
        $sop->getAudience()->add($role);

        self::assertSame(
            SopStanding::SIGN_OFF_NEEDED,
            $this->service->standingFor($sop, $this->employee($role), $this->now, null),
        );
    }

    public function testAnEmployeeWithNoRolesOnlyMatchesOpenAudiences(): void
    {
        $sop = $this->publishedSop();
        $sop->getAudience()->add($this->role(1));

        self::assertSame(
            SopStanding::NOT_APPLICABLE,
            $this->service->standingFor($sop, $this->employee(), $this->now, null),
        );
    }

    public function testSigningTheCurrentVersionMakesYouCurrent(): void
    {
        $sop = $this->publishedSop();

        self::assertSame(
            SopStanding::CURRENT,
            $this->service->standingFor($sop, $this->employee(), $this->now, $this->signOff('2026-08-20')),
        );
    }

    public function testARecheckIntervalLapsesAnOldSignOff(): void
    {
        $sop = $this->publishedSop(recheckMonths: 6);

        self::assertSame(
            SopStanding::RECHECK_DUE,
            $this->service->standingFor($sop, $this->employee(), $this->now, $this->signOff('2026-02-25')),
        );
        self::assertSame(
            SopStanding::CURRENT,
            $this->service->standingFor($sop, $this->employee(), $this->now, $this->signOff('2026-02-27')),
        );
    }

    public function testASignOffExactlyAtTheIntervalIsDue(): void
    {
        $sop = $this->publishedSop(recheckMonths: 6);

        self::assertSame(
            SopStanding::RECHECK_DUE,
            $this->service->standingFor($sop, $this->employee(), $this->now, $this->signOff('2026-02-26 12:00:00')),
        );
    }

    public function testNoIntervalMeansASignOffNeverLapses(): void
    {
        $sop = $this->publishedSop();

        self::assertSame(
            SopStanding::CURRENT,
            $this->service->standingFor($sop, $this->employee(), $this->now, $this->signOff('2020-01-01')),
        );
    }

    public function testDraftsAndArchivedSopsAreNeverActionable(): void
    {
        foreach ([SopStatus::DRAFT, SopStatus::ARCHIVED] as $status) {
            $sop = $this->publishedSop();
            $sop->setStatus($status);

            self::assertSame(
                SopStanding::NOT_APPLICABLE,
                $this->service->standingFor($sop, $this->employee(), $this->now, null),
            );
        }
    }

    private function publishedSop(?int $recheckMonths = null): Sop
    {
        $sop = new Sop();
        $sop->publish();
        $sop->setRecheckMonths($recheckMonths);
        $this->withId($sop, 1);

        return $sop;
    }

    private function employee(HotelRole ...$roles): Employee
    {
        $employee = new Employee();
        foreach ($roles as $role) {
            $employee->getHotelRoles()->add($role);
        }
        $this->withId($employee, 1);

        return $employee;
    }

    private function role(int $id): HotelRole
    {
        $role = new HotelRole();
        $this->withId($role, $id);

        return $role;
    }

    private function signOff(string $signedAt): SopSignOff
    {
        $signOff = new SopSignOff();
        $signOff->setSignedAt(new DateTime($signedAt));
        $signOff->setVersion(1);

        return $signOff;
    }
}
