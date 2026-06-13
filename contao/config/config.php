<?php

use Mandrael\ContaoMaplibreBundle\ContentElement\ContentMaplibreMap;

// Backend-Modul: Inhalte -> MapLibre Standorte
$GLOBALS['BE_MOD']['content']['maplibre_locations'] = [
    'tables' => ['tl_maplibre_location'],
    'icon'   => 'bundles/mandraelcontaomaplibre/icon.svg',
];

// Inhaltselement "MapLibre Karte" (Legacy-CTE, lauffaehig auf Contao 4.13/5.3/5.7)
$GLOBALS['TL_CTE']['media']['maplibre_map'] = ContentMaplibreMap::class;
