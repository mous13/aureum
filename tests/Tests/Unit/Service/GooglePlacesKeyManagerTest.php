<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Exception\GooglePlacesException;
use Forumify\Core\Repository\SettingRepository;
use PHPUnit\Framework\TestCase;

class GooglePlacesKeyManagerTest extends TestCase
{
    private array $settings = [];

    private function createManager(string $secret = 'test-app-secret'): GooglePlacesKeyManager
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->method('get')->willReturnCallback(
            fn(string $key) => $this->settings[$key] ?? null,
        );
        $repository->method('set')->willReturnCallback(
            function (string $key, mixed $value): void {
                $this->settings[$key] = $value;
            },
        );
        $repository->method('unset')->willReturnCallback(
            function (string $key): void {
                unset($this->settings[$key]);
            },
        );

        return new GooglePlacesKeyManager($repository, $secret);
    }

    public function testRoundTrip(): void
    {
        $manager = $this->createManager();
        $manager->setKey('AIzaSyFakeKey123');

        self::assertTrue($manager->hasKey());
        self::assertSame('AIzaSyFakeKey123', $manager->getKey());
        self::assertInstanceOf(\DateTimeImmutable::class, $manager->getKeySetAt());
    }

    public function testStoredValueIsNotPlaintext(): void
    {
        $manager = $this->createManager();
        $manager->setKey('AIzaSyFakeKey123');

        $stored = $this->settings['aureum.google_places.api_key'];
        self::assertIsString($stored);
        self::assertStringNotContainsString('AIzaSyFakeKey123', $stored);
        self::assertStringNotContainsString('AIzaSyFakeKey123', base64_decode($stored));
    }

    public function testCiphertextDiffersAcrossWrites(): void
    {
        $manager = $this->createManager();
        $manager->setKey('AIzaSyFakeKey123');
        $first = $this->settings['aureum.google_places.api_key'];
        $manager->setKey('AIzaSyFakeKey123');
        $second = $this->settings['aureum.google_places.api_key'];

        self::assertNotSame($first, $second);
    }

    public function testGetKeyThrowsWhenUnset(): void
    {
        $this->expectException(GooglePlacesException::class);
        $this->createManager()->getKey();
    }

    public function testGetKeyThrowsWithWrongSecret(): void
    {
        $this->createManager('secret-a')->setKey('AIzaSyFakeKey123');

        $this->expectException(GooglePlacesException::class);
        $this->createManager('secret-b')->getKey();
    }

    public function testRemoveKey(): void
    {
        $manager = $this->createManager();
        $manager->setKey('AIzaSyFakeKey123');
        $manager->removeKey();

        self::assertFalse($manager->hasKey());
        self::assertNull($manager->getKeySetAt());
    }
}
