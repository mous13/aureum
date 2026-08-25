<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\RetentionPolicy;
use Forumify\Core\Repository\AbstractRepository;

/** @extends AbstractRepository<RetentionPolicy> */
class RetentionPolicyRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return RetentionPolicy::class;
    }

    /** @return array<RetentionPolicy> */
    public function findEnforced(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.retainForMonths IS NOT NULL')
            ->andWhere('p.retainForMonths > 0')
            ->getQuery()
            ->getResult();
    }

    /** @return array<string, RetentionPolicy> keyed by module value */
    public function findByHotelKeyedByModule(Hotel $hotel): array
    {
        $policies = $this->createQueryBuilder('p')
            ->where('p.hotel = :hotel')
            ->setParameter('hotel', $hotel)
            ->getQuery()
            ->getResult();

        $keyed = [];
        foreach ($policies as $policy) {
            $keyed[$policy->getModule()->value] = $policy;
        }

        return $keyed;
    }

    public function findOrCreate(Hotel $hotel, Module $module): RetentionPolicy
    {
        $policy = $this->findOneBy(['hotel' => $hotel, 'module' => $module]);
        if ($policy !== null) {
            return $policy;
        }

        $policy = new RetentionPolicy();
        $policy->setHotel($hotel);
        $policy->setModule($module);

        return $policy;
    }
}
