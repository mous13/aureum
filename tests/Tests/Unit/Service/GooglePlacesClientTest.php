<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Exception\GooglePlacesException;
use Citadel\Aureum\Core\Service\GooglePlacesClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GooglePlacesClientTest extends TestCase
{
    private function createClient(callable $responseFactory): GooglePlacesClient
    {
        $keyManager = $this->createMock(GooglePlacesKeyManager::class);
        $keyManager->method('getKey')->willReturn('test-api-key');

        return new GooglePlacesClient(new MockHttpClient($responseFactory), $keyManager);
    }

    public function testSearchTextSendsKeyAndFieldMaskHeaders(): void
    {
        $client = $this->createClient(function (string $method, string $url, array $options) {
            self::assertSame('POST', $method);
            self::assertSame('https://places.googleapis.com/v1/places:searchText', $url);
            $headers = implode("\n", $options['headers']);
            self::assertStringContainsString('X-Goog-Api-Key: test-api-key', $headers);
            self::assertStringContainsString(
                'X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress',
                $headers,
            );
            $body = json_decode($options['body'], true);
            self::assertSame('Scarpetta, 88 Madison Ave', $body['textQuery']);
            self::assertSame(5, $body['pageSize']);

            return new MockResponse(json_encode([
                'places' => [
                    [
                        'id' => 'ChIJabc',
                        'displayName' => ['text' => 'Scarpetta'],
                        'formattedAddress' => '88 Madison Ave, New York',
                    ],
                ],
            ]));
        });

        $results = $client->searchText('Scarpetta, 88 Madison Ave');

        self::assertSame(
            [['id' => 'ChIJabc', 'name' => 'Scarpetta', 'address' => '88 Madison Ave, New York']],
            $results,
        );
    }

    public function testSearchTextEmptyResults(): void
    {
        $client = $this->createClient(fn() => new MockResponse('{}'));

        self::assertSame([], $client->searchText('nowhere'));
    }

    public function testGetOpeningHoursUsesMinimalFieldMask(): void
    {
        $client = $this->createClient(function (string $method, string $url, array $options) {
            self::assertSame('GET', $method);
            self::assertSame('https://places.googleapis.com/v1/places/ChIJabc', $url);
            $headers = implode("\n", $options['headers']);
            self::assertStringContainsString('X-Goog-FieldMask: regularOpeningHours', $headers);

            return new MockResponse(json_encode([
                'regularOpeningHours' => ['periods' => [['open' => ['day' => 1, 'hour' => 9, 'minute' => 0], 'close' => ['day' => 1, 'hour' => 17, 'minute' => 0]]]],
            ]));
        });

        $hours = $client->getOpeningHours('ChIJabc');

        self::assertIsArray($hours);
        self::assertArrayHasKey('periods', $hours);
    }

    public function testGetOpeningHoursNullWhenAbsent(): void
    {
        $client = $this->createClient(fn() => new MockResponse('{}'));

        self::assertNull($client->getOpeningHours('ChIJabc'));
    }

    public function testHttpErrorBecomesGooglePlacesException(): void
    {
        $client = $this->createClient(fn() => new MockResponse('denied', ['http_code' => 403]));

        $this->expectException(GooglePlacesException::class);
        $client->searchText('anything');
    }
}
