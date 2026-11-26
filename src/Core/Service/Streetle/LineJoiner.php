<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

final class LineJoiner
{
    /**
     * @param list<list<array{float, float}>> $lines
     * @return list<list<array{float, float}>>
     */
    public static function join(array $lines): array
    {
        $joined = [];
        while ($lines !== []) {
            $current = array_shift($lines);
            do {
                $extended = false;
                foreach ($lines as $i => $line) {
                    $next = self::attach($current, $line);
                    if ($next === null) {
                        continue;
                    }

                    $current = $next;
                    unset($lines[$i]);
                    $extended = true;
                }
                $lines = array_values($lines);
            } while ($extended);

            $joined[] = $current;
        }

        return $joined;
    }

    /**
     * @param list<array{float, float}> $current
     * @param list<array{float, float}> $line
     * @return list<array{float, float}>|null
     */
    private static function attach(array $current, array $line): ?array
    {
        $first = $current[0];
        $last = $current[count($current) - 1];

        return match (true) {
            $last === $line[0] => [...$current, ...array_slice($line, 1)],
            $last === $line[count($line) - 1] => [...$current, ...array_slice(array_reverse($line), 1)],
            $first === $line[count($line) - 1] => [...$line, ...array_slice($current, 1)],
            $first === $line[0] => [...array_reverse($line), ...array_slice($current, 1)],
            default => null,
        };
    }
}
