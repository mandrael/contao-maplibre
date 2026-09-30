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
    private const USER_AGENT = 'contao-maplibre/0.2.0 (+https://github.com/mandrael/contao-maplibre)';

    /**
     * Prozessinterner Rückfall, falls die Sperrdatei nicht geöffnet werden kann (z. B. read-only
     * temp-Verzeichnis). Gilt nur innerhalb dieses PHP-Prozesses, nicht prozessübergreifend.
     */
    private static float $lastRequestAt = 0.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly float $minInterval = 1.1,
        private readonly ?string $lockFile = null,
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

        $this->throttle();

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

        $lat = self::parseCoordinate($data[0]['lat'], -90.0, 90.0);
        $lng = self::parseCoordinate($data[0]['lon'], -180.0, 180.0);

        if (null === $lat || null === $lng || (0.0 === $lat && 0.0 === $lng)) {
            $this->logger?->warning(sprintf('MapLibre-Geocoding: Nominatim-Antwort mit ungültigen Koordinaten für "%s".', $address));

            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Drosselt auf höchstens eine Anfrage pro $minInterval Sekunden – prozessübergreifend über eine
     * Sperrdatei (Nominatim-Nutzungsrichtlinie: max. 1 Anfrage/Sekunde). Ist die Sperrdatei nicht
     * nutzbar, wird wenigstens der prozessinterne Abstand eingehalten.
     */
    private function throttle(): void
    {
        if ($this->minInterval <= 0.0) {
            return;
        }

        // Standard ist var/ der Installation (per services.yaml): ein fester Pfad im geteilten /tmp
        // ließe sich auf Shared Hosts per Symlink auf fremde Dateien umbiegen.
        $handle = @fopen($this->lockFile ?? sys_get_temp_dir().'/contao-maplibre-nominatim-'.md5(__DIR__).'.lock', 'c+');

        if (false === $handle) {
            $this->logger?->warning('MapLibre-Geocoding: Sperrdatei nicht verfügbar, drossle nur prozessintern.');
            $this->throttleProcessLocal();

            return;
        }

        try {
            if (!flock($handle, \LOCK_EX)) {
                $this->logger?->warning('MapLibre-Geocoding: Sperrdatei konnte nicht gesperrt werden, drossle nur prozessintern.');
                $this->throttleProcessLocal();

                return;
            }

            // Kaputter Inhalt oder Zeitstempel in der Zukunft (Uhrsprung) darf nie lange blockieren.
            $last = min((float) stream_get_contents($handle), microtime(true));
            $this->wait($last);

            $now = microtime(true);
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) $now);
            fflush($handle);
            self::$lastRequestAt = $now;

            flock($handle, \LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function throttleProcessLocal(): void
    {
        $this->wait(self::$lastRequestAt);
        self::$lastRequestAt = microtime(true);
    }

    private function wait(float $lastRequestAt): void
    {
        $remaining = $lastRequestAt + $this->minInterval - microtime(true);

        if ($remaining > 0) {
            usleep((int) round($remaining * 1_000_000));
        }
    }

    private static function parseCoordinate(mixed $value, float $min, float $max): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        if (!is_finite($float) || $float < $min || $float > $max) {
            return null;
        }

        return $float;
    }
}
