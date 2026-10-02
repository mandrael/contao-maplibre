<img src="logo.svg" alt="Contao MapLibre" width="100" align="right">

[Deutsch](README.md) | **English**

# Contao MapLibre

Privacy-friendly maps for Contao using [MapLibre GL JS](https://maplibre.org/) and
[OpenFreeMap](https://openfreemap.org/) (vector tiles based on OpenMapTiles / OpenStreetMap) – a
drop-in replacement for Google Maps. With **multi-markers**, **cluster markers**, central location
management and automatic **geocoding** (address → coordinates via Nominatim).

A single code base for the three Contao LTS versions **4.13, 5.3 and 5.7** (incl. 5.4–5.6).

---

## Features

- **Content element “MapLibre map”** – place it in any article, replacing a Google map in place.
- **Back end module “MapLibre locations”** (`tl_maplibre_location`) – central management of places with
  title, address, category, link and publication status.
- **Automation instead of upkeep:** a location that is **unpublished** automatically disappears from
  **all** maps. No manual removal of individual markers.
- **Geocoding on save:** coordinates are looked up from the address via Nominatim (OpenStreetMap) and
  cached in the database – manual coordinates remain possible.
- **Marker symbols:** a curated, map-relevant icon set ([Maki](https://labs.mapbox.com/maki-icons/),
  CC0) – chosen in the back end via a visual picker (coloured pin with a white icon, like Google My Maps).
  Group default on the map element, overridable per location; plus a **custom SVG** upload.
- **Category legend:** visitors show and hide categories (e.g. accommodation, food) with checkboxes, as in
  Google My Maps; one pin colour per category. Each location can carry a popup description and, optionally,
  an “Open in Google Maps” button (new tab).
- **Multi-markers & clustering:** any number of markers per map; optionally as summarising, numbered
  cluster circles that zoom into the region on click.
- **Familiar look:** OpenFreeMap style `bright` (alternatively `liberty`/`positron`), subtle marker
  colour, “click to activate” (the page scrolls through the map until you click it).
- **Migration from Google My Maps:** a console command imports the markers of an existing
  “Google My Maps” map (KML) as locations.
- **Reusable:** the rendering logic is encapsulated as a service (`MaplibreRenderer`) and can be used
  by your own bundles.

## Privacy

The front-end map display loads **no** Google services. Tiles come from OpenFreeMap, MapLibre GL JS
from unpkg. No tracking. Geocoding runs exclusively **in the back end on save** (not in the front end);
coordinates are cached. One exception: the optional KML import of existing "Google My Maps" maps makes
a single request to `www.google.com` for that purpose.

## Installation

```bash
composer require mandrael/contao-maplibre
vendor/bin/contao-console cache:clear
vendor/bin/contao-console contao:migrate
```

Alternatively search for `mandrael/contao-maplibre` in the **Contao Manager**, add it and update the
database.

## Usage

### 1. Create locations

In the back end under **Content → MapLibre locations**, create a location: title, address
(street, postal code, city, optional country) and a **category** (e.g. “Course venues”). On save the
**coordinates are looked up automatically** from the address (Nominatim) and stored.

- Coordinates can be **overridden manually** at any time.
- “Recalculate coordinates from address” forces a re-lookup on the next save.
- Only **published** locations (within the optional display period) appear on maps.

### 2. Insert a map

Add a content element of type **MapLibre map** and choose the marker source:

- **Locations** – individual central locations, and/or
- **Categories** – all published locations of the chosen categories, and/or
- **Custom markers (ad hoc)** – one line per marker in the format `Label; Address`. Coordinates are
  looked up automatically and cached when the element is saved.

Display options: map style, height, marker colour, **default symbol (group)**, **cluster** on/off,
“interactive immediately”. Viewport: “fit all markers automatically” (`fitBounds`) **or** a fixed
centre + zoom level.

By default the page **scrolls through the map** (wheel zoom only after a click on the map – like the
reference). The **“Map interactive immediately”** option lets the map take over wheel scrolling without a
click (capturing page scroll).

### Marker symbols

Each marker is shown as a coloured pin (marker colour) with a white icon glyph – like Google My Maps.
The symbol is resolved in this order: **location’s custom SVG** › **location’s chosen symbol** ›
**group default symbol of the map element** › plain pin without a symbol.

- In the back end (both location **and** map element) a **visual icon picker** opens a clickable grid
  of the bundled symbols.
- For an individual symbol, use the **“Custom SVG symbol”** field on the location (an SVG file from the
  file manager). Matching the pin style, it is rendered white inside the coloured pin.

### 3. Import an existing Google My Maps map

```bash
vendor/bin/contao-console contao:maplibre:import-mymaps <mid> --category="Course venues"
```

The `mid` is part of the Google My Maps embed/share URL
(`https://www.google.com/maps/d/embed?mid=<mid>`). The contained markers (name + coordinates) are
created as locations of the given category. Addresses can be added afterwards in the back end.

## Content Security Policy (CSP)

If the site uses a CSP, the following sources must be allowed:

```
script-src  https://unpkg.com;
style-src   https://unpkg.com;
connect-src https://tiles.openfreemap.org;
img-src     data: blob:;
worker-src  blob:;
```

On **Contao 5.x** these sources are added **automatically** to the page CSP if the page has one set.
On Contao 4.13 they may need to be added manually.

## Reuse in your own bundles

The map can be created programmatically – e.g. from your own catalogue/people bundle:

```php
use Mandrael\ContaoMaplibreBundle\Map\Marker;
use Mandrael\ContaoMaplibreBundle\Map\MaplibreRenderer;

$html = $renderer->render(
    [
        new Marker(47.8127, 13.0489, 'Praxis Tobar', 'Breitenfelderstr. 47a, 5020 Salzburg', '/profile/tobar'),
        // ...
    ],
    ['cluster' => true, 'height' => 500]
);
```

`$renderer` is the service `Mandrael\ContaoMaplibreBundle\Map\MaplibreRenderer` (autowireable).

## Compatibility

- **PHP:** 8.1+
- **Contao:** 4.13 LTS, 5.3 LTS and 5.7 LTS (incl. 5.4–5.6) from a single code base.

## Attribution

The required attribution “MapLibre | OpenFreeMap © OpenMapTiles Data from OpenStreetMap” is shown
automatically on the map by the OpenFreeMap style.

## Bundled icons

The symbol set under `public/icons/` is taken from [Maki](https://github.com/mapbox/maki) by Mapbox and
is licensed under **CC0 1.0** (public domain). No attribution required.

## License

MIT – see [LICENSE](LICENSE). Bundled Maki icons: CC0 1.0.
