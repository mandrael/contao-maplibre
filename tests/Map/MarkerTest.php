<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\Map;

use Mandrael\ContaoMaplibreBundle\Map\Marker;
use PHPUnit\Framework\TestCase;

class MarkerTest extends TestCase
{
    public function testToArrayOmitsEmptyOptionalFields(): void
    {
        $marker = new Marker(47.81, 13.04, 'Praxis');

        $this->assertSame(
            ['lat' => 47.81, 'lng' => 13.04, 'title' => 'Praxis'],
            $marker->toArray()
        );
    }

    public function testToArrayKeepsAllProvidedFields(): void
    {
        $marker = new Marker(47.81, 13.04, 'Praxis', 'Hauptstr. 1, 5020 Salzburg', 'https://example.com', '#abc123');

        $this->assertSame(
            [
                'lat' => 47.81,
                'lng' => 13.04,
                'title' => 'Praxis',
                'address' => 'Hauptstr. 1, 5020 Salzburg',
                'link' => 'https://example.com',
                'color' => '#abc123',
            ],
            $marker->toArray()
        );
    }

    public function testToArrayIncludesIconWhenSet(): void
    {
        $marker = new Marker(47.81, 13.04, 'Praxis', '', '', null, 'bundles/mandraelcontaomaplibre/icons/doctor.svg');

        $array = $marker->toArray();

        $this->assertSame('bundles/mandraelcontaomaplibre/icons/doctor.svg', $array['icon']);
        $this->assertArrayNotHasKey('color', $array);
    }

    public function testIsValidRejectsNullIsland(): void
    {
        $this->assertFalse((new Marker(0.0, 0.0))->isValid());
        $this->assertTrue((new Marker(47.81, 13.04))->isValid());
    }
}
