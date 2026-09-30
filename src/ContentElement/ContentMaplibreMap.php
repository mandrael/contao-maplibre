<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Mandrael\ContaoMaplibreBundle\Csp\MaplibreCspSourceRegistrar;
use Mandrael\ContaoMaplibreBundle\Helper\LocationsHelper;
use Mandrael\ContaoMaplibreBundle\Map\IconCatalog;
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

        $request = $container->get('request_stack')->getCurrentRequest();
        $basePath = null !== $request ? rtrim($request->getBasePath(), '/') : '';
        $defaultIcon = $this->resolveBundledIcon((string) $this->maplibre_default_icon, $basePath);

        $markers = [];

        // 1. Zentrale Standorte: Auswahl + Kategorien, nur veröffentlichte mit Koordinaten.
        $ids = StringUtil::deserialize($this->maplibre_locations, true);
        $categories = StringUtil::deserialize($this->maplibre_categories, true);

        foreach ($locations->findPublished($ids, $categories) as $row) {
            // Nicht-numerische Werte (z. B. defekter Altbestand) niemals auf 0 casten – Standort überspringen.
            if (!is_numeric($row['latitude'] ?? null) || !is_numeric($row['longitude'] ?? null)) {
                continue;
            }

            $icon = $this->resolveLocationIcon($row, $basePath) ?: $defaultIcon;

            $markers[] = new Marker(
                (float) $row['latitude'],
                (float) $row['longitude'],
                (string) $row['title'],
                $this->composeAddress($row),
                (string) ($row['link'] ?? ''),
                null,
                '' !== $icon ? $icon : null,
            );
        }

        // 2. Ad-hoc-Marker aus dem beim Speichern befüllten Cache (nutzen das Gruppen-Default-Icon).
        foreach (StringUtil::deserialize($this->maplibre_inline_cache, true) as $entry) {
            if (isset($entry['lat'], $entry['lng']) && is_numeric($entry['lat']) && is_numeric($entry['lng'])) {
                $markers[] = new Marker(
                    (float) $entry['lat'],
                    (float) $entry['lng'],
                    (string) ($entry['title'] ?? ''),
                    (string) ($entry['address'] ?? ''),
                    '',
                    null,
                    '' !== $defaultIcon ? $defaultIcon : null,
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

        // Nur gültiger 3- oder 6-stelliger Hex-Code (mit oder ohne "#"), sonst Standardfarbe.
        if (1 !== preg_match('/^#?[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $color)) {
            return MaplibreRenderer::DEFAULT_MARKER_COLOR;
        }

        return str_starts_with($color, '#') ? $color : '#'.$color;
    }

    /**
     * Icon eines Standorts: eigenes SVG (Datei-UUID) hat Vorrang vor dem gewählten Preset-Icon.
     *
     * @param array<string, mixed> $row
     */
    private function resolveLocationIcon(array $row, string $basePath): string
    {
        $uuid = $row['iconSvg'] ?? null;

        if (!empty($uuid)) {
            $file = FilesModel::findByUuid($uuid);

            // Nur echte SVG-Dateien verwenden (Ordner/andere Dateitypen fallen auf das Preset-Icon zurück).
            if (null !== $file && $file->path && 'file' === $file->type && str_ends_with(strtolower($file->path), '.svg')) {
                return $basePath.'/'.$file->path;
            }
        }

        return $this->resolveBundledIcon((string) ($row['icon'] ?? ''), $basePath);
    }

    private function resolveBundledIcon(string $key, string $basePath): string
    {
        return IconCatalog::has($key) ? $basePath.'/'.IconCatalog::relativeUrl($key) : '';
    }
}
