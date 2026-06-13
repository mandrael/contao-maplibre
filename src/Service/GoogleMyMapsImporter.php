<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Importiert die Marker einer "Google My Maps"-Karte (öffentliches KML) als zentrale Standorte.
 * Praktisch für die Migration bestehender Google-Karten (z. B. die NK-Kursorte-Karte). Koordinaten
 * stehen bereits im KML; nur wo sie fehlen und eine Adresse vorhanden ist, wird optional geocodiert.
 */
final class GoogleMyMapsImporter
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return array{imported: int, skipped: int, names: array<string>}
     */
    public function import(string $mid, string $category = 'Kursorte'): array
    {
        $url = sprintf('https://www.google.com/maps/d/kml?mid=%s&forcekml=1', rawurlencode($mid));
        $kml = $this->httpClient->request('GET', $url, ['timeout' => 20])->getContent();

        $placemarks = self::parseKml($kml);

        $imported = 0;
        $skipped = 0;
        $names = [];
        $sorting = 128;

        foreach ($placemarks as $placemark) {
            $existing = $this->connection->fetchOne(
                'SELECT id FROM tl_maplibre_location WHERE title = ?',
                [$placemark['name']]
            );

            if (false !== $existing) {
                ++$skipped;
                continue;
            }

            $this->connection->insert('tl_maplibre_location', [
                'tstamp' => time(),
                'title' => $placemark['name'],
                'latitude' => (string) $placemark['lat'],
                'longitude' => (string) $placemark['lng'],
                'category' => $category,
                'published' => '1',
                'sorting' => $sorting,
            ]);

            $sorting += 128;
            ++$imported;
            $names[] = $placemark['name'];
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'names' => $names];
    }

    /**
     * @return array<array{name: string, lat: float, lng: float}>
     */
    public static function parseKml(string $kml): array
    {
        $out = [];

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($kml);
        libxml_use_internal_errors($previous);

        if (false === $xml) {
            return $out;
        }

        // local-name() ignoriert den KML-Namespace; Placemarks können in Folders verschachtelt sein.
        foreach ($xml->xpath('//*[local-name()="Placemark"]') ?: [] as $placemark) {
            $nameNodes = $placemark->xpath('*[local-name()="name"]');
            $coordNodes = $placemark->xpath('.//*[local-name()="coordinates"]');

            if (empty($coordNodes)) {
                continue;
            }

            $name = $nameNodes ? trim((string) $nameNodes[0]) : '';
            $parts = explode(',', trim((string) $coordNodes[0]));

            if ('' === $name || \count($parts) < 2) {
                continue;
            }

            $lng = (float) $parts[0];
            $lat = (float) $parts[1];

            if (0.0 === $lat && 0.0 === $lng) {
                continue;
            }

            $out[] = ['name' => $name, 'lat' => $lat, 'lng' => $lng];
        }

        return $out;
    }
}
