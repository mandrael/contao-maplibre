<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\StringUtil;
use Contao\System;
use Mandrael\ContaoMaplibreBundle\Csp\MaplibreCspSourceRegistrar;
use Mandrael\ContaoMaplibreBundle\Helper\LocationsHelper;
use Mandrael\ContaoMaplibreBundle\Map\MaplibreRenderer;
use Mandrael\ContaoMaplibreBundle\Map\Marker;

/**
 * Legacy-Inhaltselement (TL_CTE): rendert eine MapLibre-Karte. Sammelt die Marker aus den zentralen
 * Standorten (Auswahl + Kategorien, nur veröffentlichte) sowie den im Element gecachten Ad-hoc-Markern.
 * Bewusst Legacy + .html5: identisches Verhalten auf Contao 4.13/5.3/5.7 (das Template ist nur eine
 * dünne JS-Hülle, Twig brächte hier keinen Mehrwert).
 */
class ContentMaplibreMap extends ContentElement
{
    protected $strTemplate = 'ce_maplibre_map';

    public function generate()
    {
        $container = System::getContainer();
        $request = $container->get('request_stack')->getCurrentRequest();

        if (null !== $request && $container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### '.($GLOBALS['TL_LANG']['CTE']['maplibre_map'][0] ?? 'MapLibre').' ###';
            $template->title = $this->headline;
            $template->id = $this->id;

            return $template->parse();
        }

        return parent::generate();
    }

    protected function compile(): void
    {
        $container = System::getContainer();

        /** @var LocationsHelper $locations */
        $locations = $container->get(LocationsHelper::class);

        /** @var MaplibreRenderer $renderer */
        $renderer = $container->get(MaplibreRenderer::class);

        $markers = [];

        // 1. Zentrale Standorte: Auswahl + Kategorien, nur veröffentlichte mit Koordinaten.
        $ids = StringUtil::deserialize($this->maplibre_locations, true);
        $categories = StringUtil::deserialize($this->maplibre_categories, true);

        foreach ($locations->findPublished($ids, $categories) as $row) {
            $markers[] = new Marker(
                (float) $row['latitude'],
                (float) $row['longitude'],
                (string) $row['title'],
                $this->composeAddress($row),
                (string) ($row['link'] ?? ''),
            );
        }

        // 2. Ad-hoc-Marker aus dem beim Speichern befüllten Cache.
        foreach (StringUtil::deserialize($this->maplibre_inline_cache, true) as $entry) {
            if (isset($entry['lat'], $entry['lng'])) {
                $markers[] = new Marker(
                    (float) $entry['lat'],
                    (float) $entry['lng'],
                    (string) ($entry['title'] ?? ''),
                    (string) ($entry['address'] ?? ''),
                );
            }
        }

        $hasCenter = '' !== (string) $this->maplibre_center_lat && '' !== (string) $this->maplibre_center_lng;

        $options = [
            'id' => 'maplibre_map_'.$this->id,
            'style' => $this->maplibre_style ?: 'bright',
            'height' => (int) $this->maplibre_height ?: 400,
            'zoom' => '' !== (string) $this->maplibre_zoom ? (float) $this->maplibre_zoom : 15.0,
            'center' => $hasCenter ? [(float) $this->maplibre_center_lng, (float) $this->maplibre_center_lat] : null,
            'fitBounds' => (bool) $this->maplibre_fit_bounds,
            'cluster' => (bool) $this->maplibre_cluster,
            'interactive' => $this->maplibre_interactive ? 'always' : 'click',
            'markerColor' => $this->normalizeColor((string) $this->maplibre_marker_color),
        ];

        $renderer->registerAssets();

        foreach ($renderer->templateData($markers, $options) as $key => $value) {
            $this->Template->{$key} = $value;
        }

        // CSP-Quellen ergänzen (nur Contao 5.x – Service nur dort registriert).
        if ($container->has(MaplibreCspSourceRegistrar::class)) {
            $container->get(MaplibreCspSourceRegistrar::class)->register();
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function composeAddress(array $row): string
    {
        $street = trim((string) ($row['street'] ?? ''));
        $cityLine = trim(trim((string) ($row['postal'] ?? '')).' '.trim((string) ($row['city'] ?? '')));
        $country = trim((string) ($row['country'] ?? ''));

        return implode(', ', array_filter([$street, $cityLine, $country]));
    }

    /**
     * Der Colorpicker speichert die Hex-Farbe ohne führendes "#". Hier normalisieren.
     */
    private function normalizeColor(string $color): string
    {
        $color = trim($color);

        if ('' === $color) {
            return MaplibreRenderer::DEFAULT_MARKER_COLOR;
        }

        return str_starts_with($color, '#') ? $color : '#'.$color;
    }
}
