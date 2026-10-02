<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\Map;

use Mandrael\ContaoMaplibreBundle\Map\MaplibreRenderer;
use Mandrael\ContaoMaplibreBundle\Map\Marker;
use PHPUnit\Framework\TestCase;

class MaplibreRendererTest extends TestCase
{
    public function testBuildConfigAppliesDefaults(): void
    {
        $config = (new MaplibreRenderer())->buildConfig([new Marker(47.81, 13.04, 'A')]);

        $this->assertSame(MaplibreRenderer::STYLES['bright'], $config['style']);
        $this->assertSame(MaplibreRenderer::MAPLIBRE_VERSION, $config['maplibreVersion']);
        $this->assertSame(MaplibreRenderer::DEFAULT_MARKER_COLOR, $config['markerColor']);
        $this->assertTrue($config['fitBounds']);
        $this->assertFalse($config['cluster']);
        $this->assertSame('click', $config['interactive']);
        $this->assertCount(1, $config['markers']);
    }

    public function testBuildConfigResolvesStyleAndFiltersInvalidMarkers(): void
    {
        $config = (new MaplibreRenderer())->buildConfig(
            [new Marker(47.81, 13.04, 'A'), new Marker(0.0, 0.0, 'invalid')],
            ['style' => 'positron', 'cluster' => true, 'interactive' => 'always', 'zoom' => 11]
        );

        $this->assertSame(MaplibreRenderer::STYLES['positron'], $config['style']);
        $this->assertTrue($config['cluster']);
        $this->assertSame('always', $config['interactive']);
        $this->assertSame(11.0, $config['zoom']);
        $this->assertCount(1, $config['markers'], 'Marker auf [0,0] muss verworfen werden.');
    }

    public function testBuildConfigPassesLegendOptions(): void
    {
        $renderer = new MaplibreRenderer();

        $this->assertFalse($renderer->buildConfig([])['legend']);

        $config = $renderer->buildConfig([], ['legend' => true, 'legendTitle' => 'Categories']);

        $this->assertTrue($config['legend']);
        $this->assertSame('Categories', $config['legendTitle']);
    }

    public function testUnknownStyleFallsBackToBright(): void
    {
        $config = (new MaplibreRenderer())->buildConfig([], ['style' => 'does-not-exist']);

        $this->assertSame(MaplibreRenderer::STYLES['bright'], $config['style']);
    }
}
