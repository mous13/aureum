<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service\Streetle;

final class StreetNameNormalizer
{
    private const ALIASES = [
        'street' => 'st',
        'saint' => 'st',
        'rd' => 'road',
        'ave' => 'avenue',
        'av' => 'avenue',
        'sq' => 'square',
        'ln' => 'lane',
        'pl' => 'place',
        'ter' => 'terrace',
        'terr' => 'terrace',
        'cl' => 'close',
        'ct' => 'court',
        'cres' => 'crescent',
        'dr' => 'drive',
        'gdns' => 'gardens',
        'gr' => 'grove',
        'pde' => 'parade',
        'wy' => 'way',
        'pk' => 'park',
        'mt' => 'mount',
        'n' => 'north',
        's' => 'south',
        'e' => 'east',
        'w' => 'west',
        'gt' => 'great',
        'lt' => 'little',
        'lwr' => 'lower',
        'upr' => 'upper',
    ];

    public function normalize(string $name): string
    {
        $name = mb_strtolower($name);
        $name = str_replace('&', ' and ', $name);
        $name = (string)preg_replace("/['’`.]/u", '', $name);
        $name = (string)preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);

        $words = [];
        foreach (explode(' ', trim($name)) as $word) {
            if ($word === '' || $word === 'the') {
                continue;
            }

            $words[] = self::ALIASES[$word] ?? $word;
        }

        return implode(' ', $words);
    }
}
