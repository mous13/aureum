<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Validator;

use Citadel\Aureum\Core\Validator\ValidOpeningTimes;
use Citadel\Aureum\Core\Validator\ValidOpeningTimesValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidOpeningTimesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ValidOpeningTimesValidator
    {
        return new ValidOpeningTimesValidator();
    }

    private function week(array $overrides = []): array
    {
        $week = [];
        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
            $week[$day] = ['closed' => true, 'ranges' => []];
        }

        return array_merge($week, $overrides);
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new ValidOpeningTimes());
        $this->assertNoViolation();
    }

    public function testValidWeekPasses(): void
    {
        $times = $this->week([
            'mon' => ['closed' => false, 'ranges' => [['12:00', '14:30'], ['18:00', '22:00']]],
            'wed' => ['closed' => false, 'ranges' => [['18:00', '01:00']]],
            'fri' => ['closed' => false, 'ranges' => [['00:00', '00:00']]],
        ]);

        $this->validator->validate($times, new ValidOpeningTimes());
        $this->assertNoViolation();
    }

    public function testMissingDayFails(): void
    {
        $times = $this->week();
        unset($times['sun']);

        $this->validator->validate($times, new ValidOpeningTimes());
        $this->buildViolation('Opening times must cover all seven days.')->assertRaised();
    }

    public function testBadTimeFormatFails(): void
    {
        $times = $this->week([
            'mon' => ['closed' => false, 'ranges' => [['25:00', '14:00']]],
        ]);

        $this->validator->validate($times, new ValidOpeningTimes());
        $this->buildViolation('Opening times contain an invalid time.')->assertRaised();
    }

    public function testClosedWithRangesFails(): void
    {
        $times = $this->week([
            'mon' => ['closed' => true, 'ranges' => [['12:00', '14:00']]],
        ]);

        $this->validator->validate($times, new ValidOpeningTimes());
        $this->buildViolation('A closed day cannot have opening hours.')->assertRaised();
    }

    public function testOverlappingRangesFail(): void
    {
        $times = $this->week([
            'mon' => ['closed' => false, 'ranges' => [['12:00', '15:00'], ['14:00', '18:00']]],
        ]);

        $this->validator->validate($times, new ValidOpeningTimes());
        $this->buildViolation('Opening hours overlap.')->assertRaised();
    }

    public function testUnknownKeyFails(): void
    {
        $times = $this->week();
        $times['funday'] = ['closed' => true, 'ranges' => []];

        $this->validator->validate($times, new ValidOpeningTimes());
        $this->buildViolation('Opening times contain an unknown day.')->assertRaised();
    }
}
