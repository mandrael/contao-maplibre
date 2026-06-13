<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Map;

use Contao\FrontendTemplate;

/**
 * Wiederverwendbare Karten-Primitive: macht aus Markern + Optionen die Template-Daten bzw.
 * fertiges HTML und registriert die Frontend-Assets. Sowohl das Inhaltselement als auch fremde
 * Bundles nutzen denselben Renderer – so bleibt die Render-Logik an einer Stelle.
 */
final class MaplibreRenderer
{
    public const MAPLIBRE_VERSION = '4.7.1';
    public const DEFAULT_MARKER_COLOR = '#4a6b3a';
    public const TEMPLATE = 'ce_maplibre_map';

    public const STYLES = [
        'bright' => 'https://tiles.openfreemap.org/styles/bright',
        'liberty' => 'https://tiles.openfreemap.org/styles/liberty',
        'positron' => 'https://tiles.openfreemap.org/styles/positron',
    ];

    private static int $counter = 0;

    public function registerAssets(): void
    {
        $GLOBALS['TL_CSS']['mandrael_maplibre'] = 'bundles/mandraelcontaomaplibre/maplibre.css|static';
        $GLOBALS['TL_JAVASCRIPT']['mandrael_maplibre'] = 'bundles/mandraelcontaomaplibre/maplibre.js|static';
    }

    /**
     * Erzeugt die JS-Konfiguration für eine Karte.
     *
     * @param array<Marker> $markers
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function buildConfig(array $markers, array $options = []): array
    {
        $style = (string) ($options['style'] ?? 'bright');
        $styleUrl = self::STYLES[$style] ?? self::STYLES['bright'];

        $markerData = [];

        foreach ($markers as $marker) {
            if ($marker->isValid()) {
                $markerData[] = $marker->toArray();
            }
        }

        return [
            'style' => $styleUrl,
            'maplibreVersion' => self::MAPLIBRE_VERSION,
            'zoom' => isset($options['zoom']) ? (float) $options['zoom'] : 15.0,
            'center' => $options['center'] ?? null, // [lng, lat] oder null
            'fitBounds' => (bool) ($options['fitBounds'] ?? true),
            'cluster' => (bool) ($options['cluster'] ?? false),
            'clusterRadius' => (int) ($options['clusterRadius'] ?? 50),
            'interactive' => 'always' === ($options['interactive'] ?? 'click') ? 'always' : 'click',
            'navigation' => (bool) ($options['navigation'] ?? true),
            'markerColor' => (string) ($options['markerColor'] ?? self::DEFAULT_MARKER_COLOR),
            'markers' => $markerData,
        ];
    }

    /**
     * Liefert die Variablen für das Template `ce_maplibre_map`.
     *
     * @param array<Marker> $markers
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function templateData(array $markers, array $options = []): array
    {
        $config = $this->buildConfig($markers, $options);

        return [
            'mapId' => (string) ($options['id'] ?? 'maplibre_map_'.(++self::$counter)),
            'mapHeight' => (int) ($options['height'] ?? 400),
            'mapConfig' => json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'mapHasMarkers' => [] !== $config['markers'],
        ];
    }

    /**
     * Rendert eine vollständige Karte als HTML-String (für die programmatische Nutzung in fremden Bundles).
     *
     * @param array<Marker> $markers
     * @param array<string, mixed> $options
     */
    public function render(array $markers, array $options = []): string
    {
        $this->registerAssets();

        $template = new FrontendTemplate(self::TEMPLATE);
        // Defaults für die vom Inhaltselement sonst gesetzten Wrapper-Variablen, damit der
        // programmatische Aufruf aus fremden Bundles keine undefinierten Template-Variablen trifft.
        $template->setData(array_merge(
            ['headline' => '', 'hl' => 'h2', 'class' => '', 'cssID' => ''],
            $this->templateData($markers, $options)
        ));

        return $template->parse();
    }
}
