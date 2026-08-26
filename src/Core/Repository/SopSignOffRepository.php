<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Sop;
use Citadel\Aureum\Core\Entity\SopSignOff;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<SopSignOff>
 */
class SopSignOffRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return SopSignOff::class;
    }

    public function findForCurrentVersion(Sop $sop, Employee $employee): ?SopSignOff
    {
        return $this->findOneBy([
            'sop' => $sop,
            'employee' => $employee,
            'version' => $sop->getVersion(),
        ]);
    }

    /** @return array<int, SopSignOff> keyed by sop id, sign-off for that sop's current version */
    public function findCurrentVersionSignOffs(Employee $employee, array $sops): array
    {
        if ($sops === []) {
            return [];
        }

        $signOffs = $this->createQueryBuilder('so')
            ->join('so.sop', 's')
            ->where('so.employee = :employee')
            ->andWhere('so.sop IN (:sops)')
            ->andWhere('so.version = s.version')
            ->setParameter('employee', $employee)
            ->setParameter('sops', $sops)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($signOffs as $signOff) {
            $result[$signOff->getSop()->getId()] = $signOff;
        }

        return $result;
    }

    /** @return array<int, SopSignOff> keyed by employee id, sign-offs for the sop's current version */
    public function findCurrentVersionSignOffsForSop(Sop $sop): array
    {
        $signOffs = $this->createQueryBuilder('so')
            ->join('so.sop', 's')
            ->where('so.sop = :sop')
            ->andWhere('so.version = s.version')
            ->setParameter('sop', $sop)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($signOffs as $signOff) {
            $result[$signOff->getEmployee()->getId()] = $signOff;
        }

        return $result;
    }

    /** @return array<int, array<int, SopSignOff>> keyed by sop id then employee id, current-version sign-offs */
    public function findCurrentVersionSignOffsForSops(array $sops): array
    {
        if ($sops === []) {
            return [];
        }

        $signOffs = $this->createQueryBuilder('so')
            ->join('so.sop', 's')
            ->where('so.sop IN (:sops)')
            ->andWhere('so.version = s.version')
            ->setParameter('sops', $sops)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($signOffs as $signOff) {
            $result[$signOff->getSop()->getId()][$signOff->getEmployee()->getId()] = $signOff;
        }

        return $result;
    }

    /** @return array<SopSignOff> */
    public function findForSop(Sop $sop): array
    {
        return $this->createQueryBuilder('so')
            ->where('so.sop = :sop')
            ->setParameter('sop', $sop)
            ->orderBy('so.version', 'DESC')
            ->addOrderBy('so.signedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
