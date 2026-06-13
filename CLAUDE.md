# contao-maplibre — Projekt-Anleitung

Öffentliches Contao-Bundle (MIT): MapLibre-Karten mit OpenFreeMap statt Google Maps. Multi-Marker +
Cluster, zentrale Standortverwaltung mit Geocoding (Nominatim), Inhaltselement. Auf Packagist als
`mandrael/contao-maplibre`, GitHub `mandrael/contao-maplibre`.

**Aktueller Stand: 0.1.0 (noch nicht released).**

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
  - Nur **Cluster-Layer** (`addSource`/`addLayer`) brauchen den geladenen Style → in `load` bzw.
    `isStyleLoaded()`.
  - **`ResizeObserver`** pro Karte → `map.resize()` + Fit, sobald der Container echte Breite hat
    (robust gegen CSS-Ladereihenfolge und 0-breite Container in versteckten Tabs).
- **Geocoding** nur einmalig beim Speichern (DCA `onsubmit_callback`), Ergebnis in der DB gecacht.
  Nominatim-Policy: eigener User-Agent, 1 Anfrage/Sek (Ad-hoc-Listener wartet zwischen Geocodes).
- **CSP:** `MaplibreCspSourceRegistrar` nur auf Contao 5.x (Extension lädt `services_csp.yaml` nur,
  wenn `CspHandler` existiert). Quellen: unpkg (script/style), tiles.openfreemap.org (connect),
  data:/blob: (img), blob: (worker).
- **Service-Sichtbarkeit:** DCA-Callbacks (Helper, Label-/Geocode-Listener) und der `MaplibreRenderer`
  sind `public: true`, weil sie über `System::getContainer()->get(...)` bzw. von Contaos Callback-System
  aufgelöst werden. `Csp/` und `ContentElement/` sind vom Service-Wildcard ausgeschlossen.

## Wiederverwendung (Designvorgabe)

`Marker` (Value Object) + `MaplibreRenderer::render($markers, $options)` sind die öffentliche API für
fremde Bundles (geplant: schlanker NK-Anwender-Katalog als Catalog-Manager-Ersatz, der diese Karte mit
Cluster-Markern + Profil-Links einbettet). Render-Logik nicht duplizieren – diesen Service nutzen.

## Qualität / Tooling

- Verifiziert gegen **Contao 5.7.6**: PHPUnit (14 Tests) grün, PHPStan Level 5 ohne Fehler.
- PHPStan **mit `--memory-limit=1G`** ausführen (Default 128M reicht für den Contao-Stack nicht).
- Optik im Browser geprüft (Pin-Modus: 3 Marker `#4a6b3a`, `fitBounds`, exakte Attribution).
  Hinweis: Headless-Browser rendern den WebGL-Canvas nicht in Screenshots und feuern kein `load` –
  Tiles/Cluster nur in echten Browsern sichtbar; DOM-Pins sind auch im Headless prüfbar.
- `php -l` deckt auch `contao/templates/ce_maplibre_map.html5` (ist PHP) ab.

## Release-Workflow (analog der anderen Bundles)

Branch `release/x.y.z` → CI grün → Packagist → Test → Merge `main` + Tag + GitHub-Release
(Release-Notes DE, EN in `<details>`-Klappbox).
