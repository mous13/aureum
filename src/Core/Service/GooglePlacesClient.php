<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Exception\GooglePlacesException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GooglePlacesClient
{
    private const BASE_URL = 'https://places.googleapis.com/v1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly GooglePlacesKeyManager $keyManager,
    ) {
    }

    public function searchText(string $query): array
    {
        $data = $this->request('POST', '/places:searchText', [
            'headers' => [
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress',
            ],
            'json' => [
                'textQuery' => $query,
                'pageSize' => 5,
            ],
        ]);

        $candidates = [];
        foreach ($data['places'] ?? [] as $place) {
            if (empty($place['id'])) {
                continue;
            }

            $candidates[] = [
                'id' => $place['id'],
                'name' => $place['displayName']['text'] ?? '',
                'address' => $place['formattedAddress'] ?? '',
            ];
        }

        return $candidates;
    }

    public function getOpeningHours(string $placeId): ?array
    {
        $data = $this->request('GET', '/places/' . $placeId, [
            'headers' => [
                'X-Goog-FieldMask' => 'regularOpeningHours',
            ],
        ]);

        $hours = $data['regularOpeningHours'] ?? null;

        return is_array($hours) ? $hours : null;
    }

    private function request(string $method, string $path, array $options): array
    {
        $options['headers']['X-Goog-Api-Key'] = $this->keyManager->getKey();

        try {
            $response = $this->httpClient->request($method, self::BASE_URL . $path, $options);
            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                throw new GooglePlacesException("Google Places request failed with status {$status}.");
            }

            return $response->toArray();
        } catch (ExceptionInterface $e) {
            throw new GooglePlacesException('Google Places request failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
