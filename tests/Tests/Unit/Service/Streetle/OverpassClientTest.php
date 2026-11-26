<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service\Streetle;

use Citadel\Aureum\Core\Exception\StreetleException;
use Citadel\Aureum\Core\Service\Streetle\OverpassClient;
use Citadel\Aureum\Core\Service\Streetle\StreetleBounds;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OverpassClientTest extends TestCase
{
    public function testItQueriesTheAreaAsSouthWestNorthEast(): void
    {
        $client = new OverpassClient(new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame(OverpassClient::ENDPOINTS[0], $url);
            parse_str($options['body'], $body);
            self::assertStringContainsString('way["highway"]["name"](53.470000,-2.260000,53.490000,-2.225000);', $body['data']);

            return new MockResponse((string)json_encode(['elements' => [['type' => 'node']]]));
        }));

        self::assertSame([['type' => 'node']], $client->fetchArea(StreetleBounds::manchesterCityCentre()));
    }

    public function testItFallsBackWhenAServerIsBusy(): void
    {
        $responses = [
            new MockResponse('All query workers are busy', ['http_code' => 504]),
            new MockResponse('not json'),
            new MockResponse((string)json_encode(['elements' => []])),
        ];

        $client = new OverpassClient(new MockHttpClient($responses));

        self::assertSame([], $client->fetchArea(StreetleBounds::manchesterCityCentre()));
    }

    public function testItGivesUpWhenEveryServerFails(): void
    {
        $client = new OverpassClient(new MockHttpClient(array_fill(0, 3, new MockResponse('', ['http_code' => 429]))));

        $this->expectException(StreetleException::class);
        $client->fetchArea(StreetleBounds::manchesterCityCentre());
    }
}
