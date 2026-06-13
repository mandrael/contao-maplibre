<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adresse -> Koordinaten via Nominatim (OpenStreetMap). Wird nur einmalig beim Speichern eines
 * Datensatzes aufgerufen; die Koordinaten werden danach in der DB gecacht. Eigener User-Agent gemäß
 * der OSM-/Nominatim-Nutzungsrichtlinie.
 */
final class GeocodingService
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    private const USER_AGENT = 'contao-maplibre/0.2 (+https://github.com/mandrael/contao-maplibre)';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address): ?array
    {
        $address = trim($address);

        if ('' === $address) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::ENDPOINT, [
                'query' => [
                    'q' => $address,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ],
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept-Language' => 'de',
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger?->warning('MapLibre-Geocoding fehlgeschlagen: '.$e->getMessage());

            return null;
        }

        if (!isset($data[0]['lat'], $data[0]['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lng' => (float) $data[0]['lon'],
        ];
    }
}
