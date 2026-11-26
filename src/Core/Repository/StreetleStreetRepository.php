<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\StreetleMap;
use Citadel\Aureum\Core\Entity\StreetleStreet;
use Forumify\Core\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<StreetleStreet>
 */
class StreetleStreetRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return StreetleStreet::class;
    }

    public function findInMap(StreetleMap $map, int $id): ?StreetleStreet
    {
        return $this->findOneBy(['map' => $map, 'id' => $id]);
    }

    public function findOneByNormalizedName(StreetleMap $map, string $normalizedName): ?StreetleStreet
    {
        return $this->findOneBy(['map' => $map, 'normalizedName' => $normalizedName]);
    }

    /**
     * @return list<array{id: int, kind: string, lines: list<list<array{float, float}>>}>
     */
    public function findShapes(StreetleMap $map): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.id', 's.kind', 's.lines')
            ->where('s.map = :map')
            ->setParameter('map', $map)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'id' => (int)$row['id'],
            'kind' => $row['kind']->value,
            'lines' => $row['lines'],
        ], $rows);
    }

    /**
     * @param list<int> $ids
     * @return list<array{id: int, name: string}>
     */
    public function findLabels(StreetleMap $map, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('s')
            ->select('s.id', 's.name')
            ->where('s.map = :map')
            ->andWhere('s.id IN (:ids)')
            ->setParameter('map', $map)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => ['id' => (int)$row['id'], 'name' => (string)$row['name']], $rows);
    }

    public function deleteForMap(StreetleMap $map): void
    {
        $this->createQueryBuilder('s')
            ->delete()
            ->where('s.map = :map')
            ->setParameter('map', $map)
            ->getQuery()
            ->execute();
    }
}
