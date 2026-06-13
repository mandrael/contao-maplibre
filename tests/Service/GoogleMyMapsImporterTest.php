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
}
