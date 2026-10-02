<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\Helper;

use Mandrael\ContaoMaplibreBundle\Helper\LocationsHelper;
use PHPUnit\Framework\TestCase;

class LocationsHelperTest extends TestCase
{
    public function testPlainDecodesWhatContaoEncodesOnSave(): void
    {
        $this->assertSame('Essen & Café', LocationsHelper::plain('Essen &amp; Café'));
        $this->assertSame('#b5532b', LocationsHelper::plain('&#35;b5532b'));
        $this->assertSame('Hotel & Gasthof', LocationsHelper::plain(' Hotel & Gasthof '));
    }
}
