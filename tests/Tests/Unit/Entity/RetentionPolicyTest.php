<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\RetentionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RetentionPolicyTest extends TestCase
{
    #[DataProvider('unenforcedProvider')]
    public function testUnsetOrZeroPeriodIsNotEnforced(?int $months): void
    {
        $policy = new RetentionPolicy();
        $policy->setRetainForMonths($months);

        self::assertFalse($policy->isEnforced());
        self::assertNull($policy->getCutoff());
    }

    public static function unenforcedProvider(): array
    {
        return [
            'never set' => [null],
            'zero' => [0],
            'negative' => [-3],
        ];
    }

    public function testCutoffIsThatManyMonthsAgo(): void
    {
        $policy = new RetentionPolicy();
        $policy->setRetainForMonths(6);

        $cutoff = $policy->getCutoff();

        self::assertNotNull($cutoff);
        self::assertTrue($policy->isEnforced());

        $expected = (new \DateTime())->modify('-6 months');
        self::assertLessThan(5, abs($cutoff->getTimestamp() - $expected->getTimestamp()));
    }

    public function testDifferentPeriodsGiveDifferentCutoffs(): void
    {
        $three = new RetentionPolicy();
        $three->setRetainForMonths(3);

        $six = new RetentionPolicy();
        $six->setRetainForMonths(6);

        self::assertGreaterThan($six->getCutoff(), $three->getCutoff());
    }

    public function testChangingThePeriodStampsWhenItChanged(): void
    {
        $policy = new RetentionPolicy();
        self::assertNull($policy->getUpdatedAt());

        $policy->setRetainForMonths(6);

        self::assertNotNull($policy->getUpdatedAt());
    }

    public function testModuleRoundTrips(): void
    {
        $policy = new RetentionPolicy();
        $policy->setModule(Module::LOST_PROPERTY);

        self::assertSame(Module::LOST_PROPERTY, $policy->getModule());
    }
}
