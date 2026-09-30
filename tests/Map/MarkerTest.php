<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\Map;

use Mandrael\ContaoMaplibreBundle\Map\Marker;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return iterable<string, array{0: float, 1: float, 2: bool}>
     */
    public static function coordinateRangeProvider(): iterable
    {
        yield 'lat über 90' => [91.0, 13.0, false];
        yield 'lng unter -180' => [47.8, -181.0, false];
        yield 'lat NAN' => [\NAN, 13.0, false];
        yield 'lng INF' => [47.8, \INF, false];
        yield 'null island' => [0.0, 0.0, false];
        yield 'gueltig' => [47.8, 13.0, true];
    }

    #[DataProvider('coordinateRangeProvider')]
    public function testIsValidChecksFiniteRange(float $lat, float $lng, bool $expected): void
    {
        $this->assertSame($expected, (new Marker($lat, $lng))->isValid());
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function linkProvider(): iterable
    {
        yield 'https' => ['https://x.at', 'https://x.at'];
        yield 'absolute Pfad' => ['/seite', '/seite'];
        yield 'relative Datei' => ['seite.html', 'seite.html'];
        yield 'Anker' => ['#a', '#a'];
        yield 'mailto' => ['mailto:a@b.at', 'mailto:a@b.at'];
        yield 'javascript' => ['javascript:alert(1)', ''];
        yield 'javascript mit Leerzeichen/Gross-Klein' => [' JavaScript:alert(1)', ''];
        yield 'data' => ['data:text/html,x', ''];
        yield 'vbscript' => ['vbscript:x', ''];
        yield 'protokoll-relativ' => ['//evil.example', ''];
        yield 'Backslash-Host' => ['\\\\evil.example', ''];
        yield 'Slash-Backslash-Host' => ['/\\evil.example', ''];
        yield 'Steuerzeichen vor //' => ["\x01//evil.example", ''];
        yield 'NUL vor //' => ["\x00//evil.example", ''];
    }

    #[DataProvider('linkProvider')]
    public function testLinkIsSanitized(string $input, string $expected): void
    {
        $this->assertSame($expected, (new Marker(47.81, 13.04, '', '', $input))->link);
    }
}
