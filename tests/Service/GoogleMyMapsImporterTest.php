<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\Service;

use Mandrael\ContaoMaplibreBundle\Service\GoogleMyMapsImporter;
use PHPUnit\Framework\TestCase;

class GoogleMyMapsImporterTest extends TestCase
{
    public function testParseKmlExtractsNameAndCoordinates(): void
    {
        $kml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <kml xmlns="http://www.opengis.net/kml/2.2">
              <Document>
                <Folder>
                  <name>Salzburg</name>
                  <Placemark>
                    <name>Praxis Tobar</name>
                    <Point><coordinates>13.0488745,47.812698,0</coordinates></Point>
                  </Placemark>
                  <Placemark>
                    <name><![CDATA[Rosa Linde - Comfort B&B]]></name>
                    <Point><coordinates>16.3489895,48.1997253,0</coordinates></Point>
                  </Placemark>
                </Folder>
              </Document>
            </kml>
            XML;

        $result = GoogleMyMapsImporter::parseKml($kml);

        $this->assertCount(2, $result);
        $this->assertSame('Praxis Tobar', $result[0]['name']);
        $this->assertEqualsWithDelta(47.812698, $result[0]['lat'], 0.0001);
        $this->assertEqualsWithDelta(13.0488745, $result[0]['lng'], 0.0001);
        $this->assertSame('Rosa Linde - Comfort B&B', $result[1]['name']);
    }

    public function testParseKmlSkipsPlacemarksWithoutCoordinates(): void
    {
        $kml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <kml xmlns="http://www.opengis.net/kml/2.2">
              <Document>
                <Placemark><name>Ohne Koordinaten</name></Placemark>
              </Document>
            </kml>
            XML;

        $this->assertSame([], GoogleMyMapsImporter::parseKml($kml));
    }

    public function testParseKmlReturnsEmptyForInvalidXml(): void
    {
        $this->assertSame([], GoogleMyMapsImporter::parseKml('kein xml'));
    }

    public function testParseKmlSkipsLineStringButKeepsPoint(): void
    {
        $kml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <kml xmlns="http://www.opengis.net/kml/2.2">
              <Document>
                <Placemark>
                  <name>Route</name>
                  <LineString><coordinates>13.0,47.8,0 13.1,47.9,0</coordinates></LineString>
                </Placemark>
                <Placemark>
                  <name>Praxis Tobar</name>
                  <Point><coordinates>13.0488745,47.812698,0</coordinates></Point>
                </Placemark>
              </Document>
            </kml>
            XML;

        $result = GoogleMyMapsImporter::parseKml($kml);

        $this->assertCount(1, $result);
        $this->assertSame('Praxis Tobar', $result[0]['name']);
    }

    public function testParseKmlSkipsNonNumericCoordinates(): void
    {
        $kml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <kml xmlns="http://www.opengis.net/kml/2.2">
              <Document>
                <Placemark>
                  <name>Kaputt</name>
                  <Point><coordinates>nicht,eine,zahl</coordinates></Point>
                </Placemark>
              </Document>
            </kml>
            XML;

        $this->assertSame([], GoogleMyMapsImporter::parseKml($kml));
    }

    public function testParseKmlSkipsNullIslandAndOutOfRange(): void
    {
        $kml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <kml xmlns="http://www.opengis.net/kml/2.2">
              <Document>
                <Placemark><name>Null</name><Point><coordinates>0,0,0</coordinates></Point></Placemark>
                <Placemark><name>Zu weit</name><Point><coordinates>200,95,0</coordinates></Point></Placemark>
                <Placemark><name>Gut</name><Point><coordinates>13.04,47.81,0</coordinates></Point></Placemark>
              </Document>
            </kml>
            XML;

        $this->assertSame(['Gut'], array_column(GoogleMyMapsImporter::parseKml($kml), 'name'));
    }

    public function testParseKmlMapsGoogleIconsToCatalog(): void
    {
        $kml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <kml xmlns="http://www.opengis.net/kml/2.2">
              <Document>
                <Placemark><name>Parkplatz</name><styleUrl>#icon-1453-nodesc</styleUrl><Point><coordinates>13.05,47.78,0</coordinates></Point></Placemark>
                <Placemark><name>Bus</name><styleUrl>#icon-1423-0288D1</styleUrl><Point><coordinates>13.05,47.78,0</coordinates></Point></Placemark>
                <Placemark><name>Unbekannt</name><styleUrl>#icon-9999</styleUrl><Point><coordinates>13.05,47.78,0</coordinates></Point></Placemark>
                <Placemark><name>Ohne Stil</name><Point><coordinates>13.05,47.78,0</coordinates></Point></Placemark>
              </Document>
            </kml>
            XML;

        $this->assertSame(['parking', 'bus', '', ''], array_column(GoogleMyMapsImporter::parseKml($kml), 'icon'));
    }

    public function testGoogleIconMappingOnlyTargetsCatalogIcons(): void
    {
        $map = (new \ReflectionClassConstant(GoogleMyMapsImporter::class, 'GOOGLE_ICONS'))->getValue();

        foreach ($map as $google => $icon) {
            $this->assertTrue(\Mandrael\ContaoMaplibreBundle\Map\IconCatalog::has($icon), "Google-Symbol $google zeigt auf unbekanntes Icon $icon");
        }
    }
}
