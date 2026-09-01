<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Service\GoogleOpeningHoursTranslator;
use PHPUnit\Framework\TestCase;

class GoogleOpeningHoursTranslatorTest extends TestCase
{
    private GoogleOpeningHoursTranslator $translator;

    protected function setUp(): void
    {
        $this->translator = new GoogleOpeningHoursTranslator();
    }

    public function testNullInput(): void
    {
        self::assertNull($this->translator->translate(null));
        self::assertNull($this->translator->translate([]));
        self::assertNull($this->translator->translate(['periods' => []]));
    }

    public function testSplitServiceAndClosedDays(): void
    {
        $result = $this->translator->translate([
            'periods' => [
                ['open' => ['day' => 1, 'hour' => 12, 'minute' => 0], 'close' => ['day' => 1, 'hour' => 14, 'minute' => 30]],
                ['open' => ['day' => 1, 'hour' => 18, 'minute' => 0], 'close' => ['day' => 1, 'hour' => 22, 'minute' => 0]],
            ],
        ]);

        self::assertSame(
            ['closed' => false, 'ranges' => [['12:00', '14:30'], ['18:00', '22:00']]],
            $result['mon'],
        );
        self::assertSame(['closed' => true, 'ranges' => []], $result['tue']);
        self::assertSame(['closed' => true, 'ranges' => []], $result['sun']);
        self::assertSame(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], array_keys($result));
    }

    public function testMidnightCrossingAttributedToOpeningDay(): void
    {
        $result = $this->translator->translate([
            'periods' => [
                ['open' => ['day' => 3, 'hour' => 18, 'minute' => 0], 'close' => ['day' => 4, 'hour' => 1, 'minute' => 0]],
            ],
        ]);

        self::assertSame(['closed' => false, 'ranges' => [['18:00', '01:00']]], $result['wed']);
        self::assertSame(['closed' => true, 'ranges' => []], $result['thu']);
    }

    public function testAlwaysOpen(): void
    {
        $result = $this->translator->translate([
            'periods' => [
                ['open' => ['day' => 0, 'hour' => 0, 'minute' => 0]],
            ],
        ]);

        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
            self::assertSame(['closed' => false, 'ranges' => [['00:00', '00:00']]], $result[$day]);
        }
    }

    public function testSundayMapping(): void
    {
        $result = $this->translator->translate([
            'periods' => [
                ['open' => ['day' => 0, 'hour' => 10, 'minute' => 0], 'close' => ['day' => 0, 'hour' => 16, 'minute' => 0]],
                ['open' => ['day' => 6, 'hour' => 9, 'minute' => 30], 'close' => ['day' => 6, 'hour' => 17, 'minute' => 0]],
            ],
        ]);

        self::assertSame(['closed' => false, 'ranges' => [['10:00', '16:00']]], $result['sun']);
        self::assertSame(['closed' => false, 'ranges' => [['09:30', '17:00']]], $result['sat']);
    }
}
