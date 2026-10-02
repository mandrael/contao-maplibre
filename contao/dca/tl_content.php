<?php

use Mandrael\ContaoMaplibreBundle\EventListener\InlineMarkerGeocodeListener;
use Mandrael\ContaoMaplibreBundle\Helper\LocationsHelper;

// Geocoding der Ad-hoc-Marker beim Speichern (Listener prüft selbst auf type == maplibre_map).
$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = [InlineMarkerGeocodeListener::class, 'onSubmit'];

// Palette des Inhaltselements
$GLOBALS['TL_DCA']['tl_content']['palettes']['maplibre_map'] =
    '{type_legend},type,headline;'
    .'{maplibre_source_legend},maplibre_locations,maplibre_categories,maplibre_inline;'
    .'{maplibre_map_legend},maplibre_style,maplibre_height,maplibre_marker_color,maplibre_category_colors,maplibre_default_icon,maplibre_cluster,maplibre_interactive,maplibre_legend,maplibre_gmaps_link;'
    .'{maplibre_focus_legend},maplibre_fit_bounds,maplibre_zoom,maplibre_center_lat,maplibre_center_lng;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_locations'] = [
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => [LocationsHelper::class, 'getLocations'],
    'eval'             => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'],
    'sql'              => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_categories'] = [
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => [LocationsHelper::class, 'getCategories'],
    'eval'             => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'],
    'sql'              => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_inline'] = [
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'decodeEntities' => true, 'tl_class' => 'clr'],
    'sql'       => 'text NULL',
];

// Interner Cache der geocodierten Ad-hoc-Marker (kein Eingabefeld).
$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_inline_cache'] = [
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_style'] = [
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['bright', 'liberty', 'positron'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['maplibre_styles'],
    'default'   => 'bright',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(32) NOT NULL default 'bright'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_height'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'maxlength' => 5, 'tl_class' => 'w50'],
    'sql'       => "smallint(5) unsigned NOT NULL default 400",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_marker_color'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['maxlength' => 6, 'colorpicker' => true, 'isHexColor' => true, 'decodeEntities' => true, 'tl_class' => 'w50 wizard'],
    'sql'       => "varchar(6) NOT NULL default '4a6b3a'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_category_colors'] = [
    'exclude'   => true,
    'inputType' => 'keyValueWizard',
    'eval'      => ['tl_class' => 'clr'],
    'sql'       => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_default_icon'] = [
    'exclude'   => true,
    'inputType' => 'maplibreIconPicker',
    'eval'      => ['tl_class' => 'clr'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_cluster'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_legend'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_interactive'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_fit_bounds'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_zoom'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'maxlength' => 2, 'tl_class' => 'w50'],
    'sql'       => "varchar(2) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_center_lat'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
    'sql'       => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_center_lng'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
    'sql'       => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['maplibre_gmaps_link'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];
