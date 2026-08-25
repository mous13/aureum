<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

class PasswordGenerator
{
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
