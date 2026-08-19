<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

class PasswordGenerator
{
    /**
     * Ambiguous glyphs are left out because these passwords get read aloud at a
     * front desk and typed by hand.
     */
    private const ALPHABET = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(int $length = 14): string
    {
        $max = strlen(self::ALPHABET) - 1;

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= self::ALPHABET[random_int(0, $max)];
        }

        return $password;
    }
}
