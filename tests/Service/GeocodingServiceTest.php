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

        $coords = (new GeocodingService($client))->geocode('Breitenfelderstr. 47a, 5020 Salzburg');

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

        $this->assertNull((new GeocodingService($client))->geocode('Nirgendwo'));
    }

    public function testGeocodeReturnsNullForEmptyAddress(): void
    {
        $this->assertNull((new GeocodingService(new MockHttpClient()))->geocode('   '));
    }
}
