<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Importiert die Marker einer "Google My Maps"-Karte (öffentliches KML) als zentrale Standorte.
 * Praktisch für die Migration bestehender Google-Karten (z. B. die NK-Kursorte-Karte). Es werden nur
 * Punkte (Point/coordinates) übernommen; Linien und Flächen werden übersprungen. Es findet kein
 * Geocoding statt – Placemarks ohne gültige Punktkoordinaten werden verworfen. Googles Marker-Symbol
 * (styleUrl "#icon-<Nummer>…") wird, wo bekannt, auf ein passendes Icon aus dem IconCatalog abgebildet.
 */
final class GoogleMyMapsImporter
{
    /**
     * Googles My-Maps-Symbolnummern → IconCatalog. Unbekannte Nummern bleiben ohne Icon (Gruppen-Default).
     */
    private const GOOGLE_ICONS = [
        '1347' => 'college',     // Seminar/Schule
        '1453' => 'parking',
        '1423' => 'bus',
        '1035' => 'lodging',
        '1015' => 'lodging',     // Hostel
        '1085' => 'restaurant',
        '991' => 'cafe',
        '1101' => 'shop',
        '973' => 'bank',
        '1504' => 'hospital',
        '1624' => 'hospital',
        '1671' => 'place-of-worship',
        '1899' => 'marker',
    ];

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
                'icon' => $placemark['icon'],
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
     * @return array<array{name: string, lat: float, lng: float, icon: string}>
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
            // Nur Punkte, keine LineString-/Polygon-Koordinaten (Linien/Flächen werden übersprungen).
            $coordNodes = $placemark->xpath('.//*[local-name()="Point"]/*[local-name()="coordinates"]');

            if (empty($coordNodes)) {
                continue;
            }

            $name = $nameNodes ? trim((string) $nameNodes[0]) : '';
            $parts = explode(',', trim((string) $coordNodes[0]));

            if ('' === $name || \count($parts) < 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
                continue;
            }

            $lng = (float) $parts[0];
            $lat = (float) $parts[1];

            if (!is_finite($lat) || !is_finite($lng) || $lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
                continue;
            }

            if (0.0 === $lat && 0.0 === $lng) {
                continue;
            }

            $styleNodes = $placemark->xpath('*[local-name()="styleUrl"]');
            $icon = '';

            if ($styleNodes && preg_match('/icon-(\d+)/', (string) $styleNodes[0], $m)) {
                $icon = self::GOOGLE_ICONS[$m[1]] ?? '';
            }

            $out[] = ['name' => $name, 'lat' => $lat, 'lng' => $lng, 'icon' => $icon];
        }

        return $out;
    }
}
