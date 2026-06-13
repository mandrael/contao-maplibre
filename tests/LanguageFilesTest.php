<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests;

use PHPUnit\Framework\TestCase;

class LanguageFilesTest extends TestCase
{
    private const FILES = ['modules.php', 'default.php', 'tl_maplibre_location.php', 'tl_content.php'];

    /**
     * @return array<string, mixed>
     */
    private function load(string $lang): array
    {
        $GLOBALS['TL_LANG'] = [];
        $base = __DIR__.'/../contao/languages/'.$lang.'/';

        foreach (self::FILES as $file) {
            include $base.$file;
        }

        return $this->flatten($GLOBALS['TL_LANG']);
    }

    public function testDeAndEnHaveIdenticalKeySets(): void
    {
        $this->assertEqualsCanonicalizing(
            array_keys($this->load('de')),
            array_keys($this->load('en')),
            'DE und EN Sprachdateien haben unterschiedliche Schlüssel.'
        );
    }

    public function testRequiredKeysPresent(): void
    {
        $de = $this->load('de');

        $required = [
            'MOD.maplibre_locations.0',
            'CTE.maplibre_map.0',
            'tl_maplibre_location.title.0',
            'tl_maplibre_location.latitude.0',
            'tl_maplibre_location.published.0',
            'tl_maplibre_location.geocodeError',
            'tl_maplibre_location.geocodeOk',
            'tl_content.maplibre_locations.0',
            'tl_content.maplibre_categories.0',
            'tl_content.maplibre_cluster.0',
            'tl_content.maplibre_styles.bright',
            'tl_content.maplibreGeocodeError',
        ];

        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $de, "Fehlender DE-Schlüssel: $key");
        }
    }

    /**
     * @param array<string, mixed> $arr
     *
     * @return array<string, mixed>
     */
    private function flatten(array $arr, string $prefix = ''): array
    {
        $out = [];

        foreach ($arr as $k => $v) {
            $key = '' === $prefix ? (string) $k : $prefix.'.'.$k;

            if (is_array($v)) {
                $out += $this->flatten($v, $key);
            } else {
                $out[$key] = $v;
            }
        }

        return $out;
    }
}
