<?php

// Legends
$GLOBALS['TL_LANG']['tl_content']['maplibre_source_legend'] = 'Marker sources';
$GLOBALS['TL_LANG']['tl_content']['maplibre_map_legend']    = 'Appearance';
$GLOBALS['TL_LANG']['tl_content']['maplibre_focus_legend']  = 'Viewport & zoom';

// Fields
$GLOBALS['TL_LANG']['tl_content']['maplibre_locations']    = ['Locations', 'Select individual central locations.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_categories']   = ['Categories', 'Show all published locations of these categories.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_inline']       = ['Custom markers (ad hoc)', 'One line per marker in the format "Label; Address". Coordinates are determined and stored automatically on save.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_style']        = ['Map style', 'OpenFreeMap style.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_height']       = ['Height (pixels)', 'Height of the map in pixels.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_marker_color'] = ['Marker colour', 'Hex colour of the markers (default 4a6b3a).'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_default_icon']  = ['Default symbol (group)', 'Symbol for all markers of this map. Individual locations can override it.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_cluster']      = ['Cluster markers', 'For many markers, show summarising numbered circles; a click zooms into the region.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_category_colors'] = ['Colour per category', 'Left the category name as used on the location, right the hex colour (e.g. 2f6690). Categories not listed use the marker colour.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_legend']       = ['Show category legend', 'Visitors can show and hide categories on the map (e.g. accommodation, food).'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_interactive']  = ['Map interactive immediately (captures page scroll)', 'Default (off): scrolling over the map keeps scrolling the page – a click on the map first activates wheel zoom and panning (like the reference). On: the map reacts to wheel/drag immediately and captures page scrolling.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_fit_bounds']   = ['Fit all markers automatically', 'Choose zoom and viewport automatically so that all markers are visible (overrides centre/zoom).'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_zoom']         = ['Zoom level', 'Used when not fitting automatically (default 15).'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_center_lat']   = ['Centre latitude', 'Optional fixed map centre.'];
$GLOBALS['TL_LANG']['tl_content']['maplibre_center_lng']   = ['Centre longitude', 'Optional fixed map centre.'];

// Style options
$GLOBALS['TL_LANG']['tl_content']['maplibre_styles']['bright']   = 'Bright';
$GLOBALS['TL_LANG']['tl_content']['maplibre_styles']['liberty']  = 'Liberty';
$GLOBALS['TL_LANG']['tl_content']['maplibre_styles']['positron'] = 'Positron (subtle)';

// Messages
$GLOBALS['TL_LANG']['tl_content']['maplibreGeocodeError'] = 'Geocoding failed for: %s';
$GLOBALS['TL_LANG']['tl_content']['maplibre_gmaps_link']   = ['Google Maps link in popup', 'Shows a small “Open in Google Maps” button below each marker (new tab), e.g. for directions.'];
