<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Service\PasswordGenerator;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorTest extends TestCase
{
    public function testDefaultLength(): void
    {
        self::assertSame(14, strlen((new PasswordGenerator())->generate()));
    }

    public function testRespectsRequestedLength(): void
    {
        self::assertSame(20, strlen((new PasswordGenerator())->generate(20)));
    }

    public function testPasswordsAreNotRepeated(): void
    {
        $generator = new PasswordGenerator();

        $passwords = [];
        for ($i = 0; $i < 200; $i++) {
            $passwords[] = $generator->generate();
        }

        self::assertCount(200, array_unique($passwords));
    }

    /**
     * These get read aloud at a front desk and typed by hand, so glyphs that are
     * easily confused have to stay out.
     */
    public function testExcludesAmbiguousCharacters(): void
    {
        $generator = new PasswordGenerator();

        $combined = '';
        for ($i = 0; $i < 200; $i++) {
            $combined .= $generator->generate();
        }

        self::assertDoesNotMatchRegularExpression('/[0O1lI]/', $combined);
    }

    public function testUsesAMixOfCharacterClasses(): void
    {
        $generator = new PasswordGenerator();

        $combined = '';
        for ($i = 0; $i < 50; $i++) {
            $combined .= $generator->generate();
        }

        self::assertMatchesRegularExpression('/[a-z]/', $combined);
        self::assertMatchesRegularExpression('/[A-Z]/', $combined);
        self::assertMatchesRegularExpression('/[2-9]/', $combined);
    }
}
