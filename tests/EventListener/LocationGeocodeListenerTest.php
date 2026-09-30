<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\EventListener;

use Mandrael\ContaoMaplibreBundle\EventListener\LocationGeocodeListener;
use PHPUnit\Framework\TestCase;

class LocationGeocodeListenerTest extends TestCase
{
    public function testShouldGeocodeWithoutCoordinates(): void
    {
        $this->assertTrue(LocationGeocodeListener::shouldGeocode(false, false, '', 'adresse'));
    }

    public function testShouldNotGeocodeWhenFingerprintMatches(): void
    {
        $this->assertFalse(LocationGeocodeListener::shouldGeocode(true, false, 'adresse', 'adresse'));
    }

    public function testShouldNotGeocodeWhenFingerprintEmpty(): void
    {
        // Altbestand/KML-Import/manuell gesetzte Koordinaten: leerer Fingerabdruck fasst Koordinaten nicht an.
        $this->assertFalse(LocationGeocodeListener::shouldGeocode(true, false, '', 'adresse'));
    }

    public function testShouldGeocodeWhenFingerprintDiffers(): void
    {
        $this->assertTrue(LocationGeocodeListener::shouldGeocode(true, false, 'alte adresse', 'neue adresse'));
    }

    public function testShouldGeocodeWhenRegeocodeChecked(): void
    {
        $this->assertTrue(LocationGeocodeListener::shouldGeocode(true, true, 'adresse', 'adresse'));
    }

    public function testNormalizeAddressLowercasesAndCollapsesWhitespace(): void
    {
        $this->assertSame(
            'hauptplatz 1, 8010 graz, österreich',
            LocationGeocodeListener::normalizeAddress('Hauptplatz  1,   8010  Graz, Österreich')
        );
    }
}
