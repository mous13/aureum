<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidOpeningTimesValidator extends ConstraintValidator
{
    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidOpeningTimes) {
            throw new UnexpectedTypeException($constraint, ValidOpeningTimes::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_array($value)) {
            $this->context->buildViolation('Opening times are malformed.')->addViolation();
            return;
        }

        foreach (array_keys($value) as $key) {
            if (!in_array($key, self::DAYS, true)) {
                $this->context->buildViolation('Opening times contain an unknown day.')->addViolation();
                return;
            }
        }

        if (count($value) !== 7) {
            $this->context->buildViolation('Opening times must cover all seven days.')->addViolation();
            return;
        }

        foreach ($value as $day) {
            if (!is_array($day) || !is_bool($day['closed'] ?? null) || !is_array($day['ranges'] ?? null)) {
                $this->context->buildViolation('Opening times are malformed.')->addViolation();
                return;
            }

            if ($day['closed'] && $day['ranges'] !== []) {
                $this->context->buildViolation('A closed day cannot have opening hours.')->addViolation();
                return;
            }

            if (!$this->validateRanges($day['ranges'])) {
                return;
            }
        }
    }

    private function validateRanges(array $ranges): bool
    {
        $intervals = [];
        foreach ($ranges as $range) {
            if (!is_array($range) || count($range) !== 2) {
                $this->context->buildViolation('Opening times are malformed.')->addViolation();
                return false;
            }

            [$start, $end] = $range;
            if (!$this->isTime($start) || !$this->isTime($end)) {
                $this->context->buildViolation('Opening times contain an invalid time.')->addViolation();
                return false;
            }

            $startMinutes = $this->toMinutes($start);
            $endMinutes = $this->toMinutes($end);
            if ($endMinutes <= $startMinutes) {
                $endMinutes += 24 * 60;
            }

            $intervals[] = [$startMinutes, $endMinutes];
        }

        usort($intervals, static fn(array $a, array $b) => $a[0] <=> $b[0]);
        for ($i = 1; $i < count($intervals); $i++) {
            if ($intervals[$i][0] < $intervals[$i - 1][1]) {
                $this->context->buildViolation('Opening hours overlap.')->addViolation();
                return false;
            }
        }

        return true;
    }

    private function isTime(mixed $value): bool
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return ((int)$hours) * 60 + (int)$minutes;
    }
}
