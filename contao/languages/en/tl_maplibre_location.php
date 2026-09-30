<?php

// Legends
$GLOBALS['TL_LANG']['tl_maplibre_location']['location_legend'] = 'Location';
$GLOBALS['TL_LANG']['tl_maplibre_location']['address_legend']  = 'Address';
$GLOBALS['TL_LANG']['tl_maplibre_location']['coords_legend']   = 'Coordinates';
$GLOBALS['TL_LANG']['tl_maplibre_location']['marker_legend']   = 'Marker symbol';
$GLOBALS['TL_LANG']['tl_maplibre_location']['link_legend']     = 'Link';
$GLOBALS['TL_LANG']['tl_maplibre_location']['publish_legend']  = 'Publication';

// Fields
$GLOBALS['TL_LANG']['tl_maplibre_location']['title']              = ['Title', 'Name of the location (shown in the popup).'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['category']           = ['Category', 'Free-form category for grouping (e.g. "Course venues").'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['street']             = ['Street and number', 'Basis for the automatic coordinate lookup.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['postal']             = ['Postal code', ''];
$GLOBALS['TL_LANG']['tl_maplibre_location']['city']               = ['City', ''];
$GLOBALS['TL_LANG']['tl_maplibre_location']['country']            = ['Country', 'Optional – improves the accuracy of the address lookup.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['maplibre_regeocode'] = ['Recalculate coordinates from address', 'Recalculate the coordinates from the address on save (overwrites manually set values).'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['latitude']           = ['Latitude', 'Determined from the address, can be overridden manually.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['longitude']          = ['Longitude', 'Determined from the address, can be overridden manually.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['icon']               = ['Marker symbol', 'Symbol for this location (empty = default pin). Overrides the group default symbol of the map element.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['iconSvg']            = ['Custom SVG symbol', 'Optional: a custom SVG file as the marker symbol. Takes precedence over the selected symbol.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['link']               = ['Link (URL)', 'Optional link in the popup, e.g. to a detail page.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['published']          = ['Publish location', 'Only published locations appear on the maps.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['start']              = ['Show from', 'Show the location only from this time on.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['stop']               = ['Show until', 'Show the location only until this time.'];

// Operations
$GLOBALS['TL_LANG']['tl_maplibre_location']['new']    = ['New location', 'Create a new location'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['edit']   = ['Edit', 'Edit location ID %s'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['copy']   = ['Duplicate', 'Duplicate location ID %s'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['delete'] = ['Delete', 'Delete location ID %s'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['toggle'] = ['Publish', 'Publish/hide location ID %s'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['show']   = ['Details', 'Show location ID %s'];

// Messages
$GLOBALS['TL_LANG']['tl_maplibre_location']['geocodeError'] = 'Geocoding failed. Please enter the coordinates manually.';
$GLOBALS['TL_LANG']['tl_maplibre_location']['geocodeOk']    = 'Coordinates determined: %s, %s';

// Tooltips of the status dots in the list
$GLOBALS['TL_LANG']['tl_maplibre_location']['coordsAvailable'] = 'Coordinates available';
$GLOBALS['TL_LANG']['tl_maplibre_location']['coordsMissing']   = 'No coordinates';
