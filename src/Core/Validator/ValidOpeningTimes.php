<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class ValidOpeningTimes extends Constraint
{
}
