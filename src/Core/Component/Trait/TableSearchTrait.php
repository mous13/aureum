<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component\Trait;

use Doctrine\ORM\QueryBuilder;

trait TableSearchTrait
{
    protected function applyTextSearch(QueryBuilder $qb, array $search, array $fields): QueryBuilder
    {
        foreach ($fields as $column => $path) {
            $term = trim((string)($search[$column] ?? ''));
            if ($term === '') {
                continue;
            }

            $parameter = 'search_' . preg_replace('/\W/', '_', $column);
            $qb
                ->andWhere(sprintf('LOWER(%s) LIKE LOWER(:%s)', $path, $parameter))
                ->setParameter($parameter, '%' . $term . '%');
        }

        return $qb;
    }

    protected function renderText(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES);
    }

    protected function renderLocalDate(?\DateTimeInterface $date, string $timezone): string
    {
        if ($date === null) {
            return '';
        }

        try {
            $zone = new \DateTimeZone($timezone);
        } catch (\Exception) {
            $zone = new \DateTimeZone('UTC');
        }

        return \DateTimeImmutable::createFromInterface($date)
            ->setTimezone($zone)
            ->format('d/m/y H:i');
    }
}
