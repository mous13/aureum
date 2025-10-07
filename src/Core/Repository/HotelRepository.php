<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Exception;
use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Hotel>
 */
class HotelRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Hotel::class;
    }

    public function findByIdOrFail(int $id): Hotel
    {
        $hotel = $this->find($id);
        return $hotel;
    }

    public function findByCode(string $code): ?Hotel
    {
        return $this->createQueryBuilder('h')
            ->where('h.code = :code')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }
}