<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit;

/**
 * Entity ids are assigned by Doctrine, so unit tests that need identity have to
 * set them directly.
 */
trait EntityIdTrait
{
    private function withId(object $entity, int $id): object
    {
        $reflection = new \ReflectionClass($entity);
        while (!$reflection->hasProperty('id')) {
            $reflection = $reflection->getParentClass();
            if ($reflection === false) {
                throw new \LogicException('No id property on ' . $entity::class);
            }
        }

        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);

        return $entity;
    }
}
