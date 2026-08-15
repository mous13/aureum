<?php

declare(strict_types=1);

namespace Tests\Tests\Unit\Core\Controller;

use Citadel\Aureum\Core\Controller\InventoryMovementController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Routing\Attribute\Route;

class InventoryMovementRoutesTest extends TestCase
{
    public function testNoTwoRoutesShareAPath(): void
    {
        $reflection = new ReflectionClass(InventoryMovementController::class);

        $paths = [];
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(Route::class) as $attribute) {
                /** @var Route $route */
                $route = $attribute->newInstance();
                $paths[] = $route->path;
            }
        }

        self::assertSame(
            array_unique($paths),
            $paths,
            sprintf(
                'Two actions on %s declare the same path. The route matcher resolves such collisions by declaration order, so whichever method is more permissive on HTTP methods silently swallows the other.',
                InventoryMovementController::class,
            ),
        );
    }
}
