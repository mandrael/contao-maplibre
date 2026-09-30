<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\Service;

use Mandrael\ContaoMaplibreBundle\Service\GeocodingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GeocodingServiceTest extends TestCase
{
    public function testGeocodeReturnsCoordinates(): void
    {
        $client = new MockHttpClient(new MockResponse(
            '[{"lat":"47.8126980","lon":"13.0488745"}]',
            ['response_headers' => ['content-type' => 'application/json']]
        ));

        $coords = (new GeocodingService($client, null, 0))->geocode('Breitenfelderstr. 47a, 5020 Salzburg');

        $this->assertNotNull($coords);
        $this->assertEqualsWithDelta(47.8126980, $coords['lat'], 0.0001);
        $this->assertEqualsWithDelta(13.0488745, $coords['lng'], 0.0001);
    }

    public function testGeocodeReturnsNullOnEmptyResult(): void
    {
        $client = new MockHttpClient(new MockResponse(
            '[]',
            ['response_headers' => ['content-type' => 'application/json']]
        ));

        $this->assertNull((new GeocodingService($client, null, 0))->geocode('Nirgendwo'));
    }

    public function testGeocodeReturnsNullForEmptyAddress(): void
    {
        $this->assertNull((new GeocodingService(new MockHttpClient(), null, 0))->geocode('   '));
    }

    public function testGeocodeReturnsNullForNonNumericLatitude(): void
    {
        $client = new MockHttpClient(new MockResponse(
            '[{"lat":"nicht-numerisch","lon":"13.0"}]',
            ['response_headers' => ['content-type' => 'application/json']]
        ));

        $this->assertNull((new GeocodingService($client, null, 0))->geocode('Irgendwo'));
    }

    public function testGeocodeReturnsNullForEmptyLatitude(): void
    {
        $client = new MockHttpClient(new MockResponse(
            '[{"lat":"","lon":"13.0"}]',
            ['response_headers' => ['content-type' => 'application/json']]
        ));

        $this->assertNull((new GeocodingService($client, null, 0))->geocode('Irgendwo'));
    }

    public function testGeocodeReturnsNullForCoordinatesOutOfRange(): void
    {
        $client = new MockHttpClient(new MockResponse(
            '[{"lat":"91.0","lon":"13.0"}]',
            ['response_headers' => ['content-type' => 'application/json']]
        ));

        $this->assertNull((new GeocodingService($client, null, 0))->geocode('Irgendwo'));
    }

    public function testGeocodeSendsUserAgentHeader(): void
    {
        $seenHeaders = [];

        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$seenHeaders) {
            $seenHeaders = $options['headers'] ?? [];

            return new MockResponse(
                '[{"lat":"47.8","lon":"13.0"}]',
                ['response_headers' => ['content-type' => 'application/json']]
            );
        });

        (new GeocodingService($client, null, 0))->geocode('Irgendwo');

        $found = false;

        foreach ($seenHeaders as $header) {
            if (str_starts_with($header, 'User-Agent: contao-maplibre/0.2.0')) {
                $found = true;
            }
        }

        $this->assertTrue($found, 'User-Agent-Header wurde nicht wie erwartet gesendet.');
    }

    public function testThrottleEnforcesMinimumInterval(): void
    {
        $client = new MockHttpClient(function () {
            return new MockResponse(
                '[{"lat":"47.8","lon":"13.0"}]',
                ['response_headers' => ['content-type' => 'application/json']]
            );
        });

        $service = new GeocodingService($client, null, 0.2);

        $service->geocode('Adresse A');
        $start = microtime(true);
        $service->geocode('Adresse B');
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(0.19, $elapsed);
    }

    public function testGeocodeReturnsNullForNullIsland(): void
    {
        $client = new MockHttpClient(new MockResponse(
            '[{"lat":"0","lon":"0"}]',
            ['response_headers' => ['content-type' => 'application/json']]
        ));

        $this->assertNull((new GeocodingService($client, null, 0))->geocode('Irgendwo'));
    }

    public function testThrottleIsSharedAcrossInstancesViaLockFile(): void
    {
        $lock = tempnam(sys_get_temp_dir(), 'mlt');
        $client = new MockHttpClient(fn () => new MockResponse(
            '[{"lat":"47.8","lon":"13.0"}]',
            ['response_headers' => ['content-type' => 'application/json']]
        ));

        // Zwei Instanzen (wie zwei PHP-Prozesse) teilen sich nur die Sperrdatei.
        (new GeocodingService($client, null, 0.2, $lock))->geocode('A');
        $start = microtime(true);
        (new GeocodingService($client, null, 0.2, $lock))->geocode('B');
        $elapsed = microtime(true) - $start;
        unlink($lock);

        $this->assertGreaterThanOrEqual(0.19, $elapsed);
    }

    public function testFutureTimestampInLockFileDoesNotBlock(): void
    {
        $lock = tempnam(sys_get_temp_dir(), 'mlt');
        file_put_contents($lock, '1e25');
        $client = new MockHttpClient(new MockResponse(
            '[{"lat":"47.8","lon":"13.0"}]',
            ['response_headers' => ['content-type' => 'application/json']]
        ));

        $start = microtime(true);
        (new GeocodingService($client, null, 0.2, $lock))->geocode('A');
        $elapsed = microtime(true) - $start;
        unlink($lock);

        $this->assertLessThan(0.5, $elapsed);
    }
}
