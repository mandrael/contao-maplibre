<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\Map;

use Mandrael\ContaoMaplibreBundle\Map\IconCatalog;
use PHPUnit\Framework\TestCase;

class IconCatalogTest extends TestCase
{
    public function testHasOnlyAcceptsKnownKeys(): void
    {
        $this->assertTrue(IconCatalog::has('college'));
        $this->assertTrue(IconCatalog::has('marker'));
        $this->assertFalse(IconCatalog::has(''));
        $this->assertFalse(IconCatalog::has('does-not-exist'));
    }

    public function testRelativeUrlPointsToBundleAsset(): void
    {
        $this->assertSame('bundles/mandraelcontaomaplibre/icons/college.svg', IconCatalog::relativeUrl('college'));
    }

    public function testKeysAreUniqueAndNonEmpty(): void
    {
        $keys = IconCatalog::keys();

        $this->assertNotEmpty($keys);
        $this->assertSame(array_values(array_unique($keys)), $keys, 'Icon-Keys müssen eindeutig sein.');
    }
}
