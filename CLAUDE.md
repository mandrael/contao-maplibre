# contao-maplibre — Projekt-Anleitung

Öffentliches Contao-Bundle (MIT): MapLibre-Karten mit OpenFreeMap statt Google Maps. Multi-Marker +
Cluster, Marker-Symbole (Maki-Icon-Picker + eigenes SVG), zentrale Standortverwaltung mit Geocoding
(Nominatim), Inhaltselement. GitHub `mandrael/contao-maplibre` (Packagist-Erstanmeldung noch offen).

**Aktueller Stand: 0.2.0 (noch nicht getaggt/released). Praktisch auf DDEV 4.13/5.3/5.7 verifiziert;
Live-Test auf nkinstitute.at + Packagist-Release offen.**

## Höchste Regel: Zweisprachigkeit (DE-Quelle, EN-Spiegel)

Jede nutzersichtbare Zeichenkette erhält parallel ein englisches Pendant. Sprachdateien als
**PHP-Arrays** in `contao/languages/{de,en}/`. Der `LanguageFilesTest` erzwingt identische Schlüssel.

## Kernfakten / Architektur

- **Kompatibilität:** Contao 4.13, 5.3, 5.7 LTS; PHP ≥ 8.1. Erst Contao 6 (Wegfall der
  Legacy-Template-Engine) erfordert ein Upgrade.
- **Layout (modern, wie `contao-turnstile`):** Root-`contao/`, `config/services.yaml`, `public/`,
  `src/DependencyInjection/MandraelContaoMaplibreExtension.php`. **Nicht** `src/Resources/contao/`.
- **Namespace:** `Mandrael\ContaoMaplibreBundle\`. Asset-Pfad `bundles/mandraelcontaomaplibre/`.
- **Rendering = Legacy-Inhaltselement** (`\Contao\ContentElement` + `$GLOBALS['TL_CTE']['media']` +
  `ce_maplibre_map.html5`). Bewusst kein moderner Twig-Fragment-Controller: dessen `getResponse()`
  erwartet auf 5.x `FragmentTemplate`, auf 4.13 `\Contao\Template` (am Quellcode verifiziert) – die
  Signaturen sind inkompatibel. Da das Template nur eine dünne JS-Hülle ist, bringt Twig keinen
  Mehrwert, der die Cross-Version-Friktion rechtfertigt. (Eine spätere 5.3+-only-Version kann auf Twig.)
- **Frontend:** `public/maplibre.js` lädt MapLibre 4.7.1 von unpkg, initialisiert alle
  `.maplibre-map[data-maplibre]`. Wichtig:
  - **HTML-Marker + Kamera (`fitBounds`) sofort setzen**, NICHT auf `map.on('load')` warten (Marker
    brauchen den Style nicht; robust gegen verzögertes/ausbleibendes `load`).
  - **Cluster-Modus = HTML-Marker** (kein Circle-Layer): GeoJSON-Source `cluster:true` + unsichtbarer
    Hilfs-Layer; `map.on('render')` + `querySourceFeatures()` synchronisieren HTML-Marker (Einzelpunkte =
    dieselben Icon-Pins, Cluster = nummerierte Kreise, Klick → `getClusterExpansionZoom`). Hängt am
    Rendering → nur im echten Browser sichtbar (Headless feuert kein `render`).
  - **`ResizeObserver`** pro Karte → `map.resize()` + Fit, sobald der Container echte Breite hat
    (robust gegen CSS-Ladereihenfolge und 0-breite Container in versteckten Tabs).
  - **Scroll-Verhalten:** Default `interactive: 'click'` = Klick-zum-Aktivieren → `scrollZoom`/`dragPan`
    aus, die Seite scrollt durch die Karte; ein Klick aktiviert. Feld `maplibre_interactive` ('always')
    schaltet auf „sofort interaktiv" (fängt Seiten-Scroll ab). Standard = Durchscrollen (wie Vorlage).
- **Marker-Symbole:** kuratiertes Maki-Set (CC0) in `public/icons/` + `IconCatalog` (statisch). Visueller
  Backend-Picker `IconPickerWidget` (registriert via `$GLOBALS['BE_FFL']['maplibreIconPicker']`, NICHT als
  Service). Felder `icon`/`iconSvg` (Standort) + `maplibre_default_icon` (Element); Auflösung im Element:
  eigenes SVG > Standort-Icon > Gruppen-Default > schlichter Pin. Frontend: farbiger Pin + weißer Glyph
  via CSS-Maske (`maplibre.js buildPinElement`).
- **Geocoding** nur einmalig beim Speichern (DCA `onsubmit_callback`), Ergebnis in der DB gecacht.
  Nominatim-Policy: eigener User-Agent, 1 Anfrage/Sek (Ad-hoc-Listener wartet zwischen Geocodes).
- **CSP:** `MaplibreCspSourceRegistrar` nur auf Contao 5.x (Extension lädt `services_csp.yaml` nur,
  wenn `CspHandler` existiert). Quellen: unpkg (script/style), tiles.openfreemap.org (connect),
  data:/blob: (img), blob: (worker).
- **Service-Sichtbarkeit:** DCA-Callbacks (Helper, Label-/Geocode-Listener) und der `MaplibreRenderer`
  sind `public: true`, weil sie über `System::getContainer()->get(...)` bzw. von Contaos Callback-System
  aufgelöst werden. `Csp/`, `ContentElement/`, `Widget/`, `Map/Marker.php` und `Map/IconCatalog.php`
  sind vom Service-Wildcard ausgeschlossen.

## Wiederverwendung (Designvorgabe)

`Marker` (Value Object) + `MaplibreRenderer::render($markers, $options)` sind die öffentliche API für
fremde Bundles (geplant: schlanker NK-Anwender-Katalog als Catalog-Manager-Ersatz, der diese Karte mit
Cluster-Markern + Profil-Links einbettet). Render-Logik nicht duplizieren – diesen Service nutzen.

## Qualität / Tooling

- Verifiziert gegen **Contao 5.7.6**: PHPUnit (18 Tests) grün, PHPStan Level 5 ohne Fehler.
- PHPStan **mit `--memory-limit=1G`** ausführen (Default 128M reicht für den Contao-Stack nicht).
- **DDEV-Praxistest auf 4.13/5.3/5.7** (additiv in `~/contao-ts/ts-cto*`): Migration, BE-Modul, DCA,
  Services/CSP, `.html5`-Render, Icon-Picker-Widget, KML-Import (11 Kursorte). Path-Repo bei Änderung
  zusätzlich nach `vendor/mandrael/contao-maplibre/` rsyncen + `cache:clear` (composer kopiert bei
  gleicher `@dev`-Version nicht neu).
- **Optik bestätigt:** Pin-/Icon-Pins im Headless (DOM), Cluster + Tiles in echtem Chrome. Headless feuert
  kein `render`/`load` → WebGL-abhängige Cluster/Tiles dort unsichtbar; DOM-Pins schon.
- `php -l` deckt auch `contao/templates/ce_maplibre_map.html5` (ist PHP) ab.

## Release-Workflow (analog der anderen Bundles)

Branch `release/x.y.z` → CI grün → Packagist → Test → Merge `main` + Tag + GitHub-Release
(Release-Notes DE, EN in `<details>`-Klappbox).
