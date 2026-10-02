# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden hier dokumentiert.
Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).

## [0.2.0] – noch nicht veröffentlicht

### Hinzugefügt
- **Kategorien-Legende:** Option im Karten-Element; Besucher blenden Kategorien (z. B. Übernachtung,
  Essen) per Checkbox ein und aus – wie bei Google My Maps. Funktioniert im Pin- und Cluster-Modus,
  vor der Aktivierung der Karte und auf schmalen Karten eingeklappt.
- **Beschreibung je Standort:** kurzer Text im Popup (Zeilenumbrüche bleiben erhalten), z. B. Ruhetage.
- **Icons Bus und Bank** (Maki, CC0); der KML-Import ordnet Googles Marker-Symbole passenden Icons zu.
- **Marker-Symbole:** kuratiertes Maki-Icon-Set (CC0, `public/icons/`) mit visuellem Backend-Picker;
  Gruppen-Standard im Karten-Element, pro Standort überschreibbar; zusätzlich eigenes SVG hochladbar.
  Darstellung als farbiger Pin mit weißem Icon-Glyph (wie Google My Maps).

### Geändert
- **Cluster-Modus** rendert jetzt über HTML-Marker: Einzelpunkte zeigen dieselben (Icon-)Pins wie der
  Pin-Modus, Cluster als nummerierte Kreise (Klick zoomt in die Region). Entfernt die Abhängigkeit von
  Style-Glyph-Fonts für die Cluster-Beschriftung.
- Beschriftung der Option **„Karte sofort interaktiv"** klargestellt: Im Standard (deaktiviert) scrollt
  die Seite durch die Karte; ein Klick aktiviert Mausrad-Zoom/Verschieben.

### Behoben
- **Ankerlinks im Popup:** `#anker` springt auf die aktuelle Seite, nicht wegen Contaos `<base href>`
  auf die Startseite.
- **Theme-unabhängige Darstellung:** Zoom-Knöpfe, Popup (Schließer, Titel, Adresse, Link) und Attribution
  haben feste Stile und übernehmen keine globalen `button`-/`a`-/Schriftstile des Themes mehr.
- **Icon „Schule" entfernt** (Apfel und Bleistift passen nicht zu Ausbildungsorten); für Bildung dient
  der Doktorhut (`college`, „Bildung / Ausbildung").
- **Kartenhöhe bei aktiver CSP:** wird jetzt per Skript (CSSOM) statt als Inline-`style`-Attribut
  gesetzt, damit die Karte sichtbar bleibt, wenn `style-src` kein `'unsafe-inline'` erlaubt.
- **Backend-Dark-Mode:** Status-Punkte und Ort-Suffix der Standort-Liste sowie der
  Icon-Picker nutzen Contao-CSS-Klassen/-Variablen (`tl_gray`/`tl_green`/`tl_red`,
  `--form-bg`/`--form-border`/`--text`/`--gray`/`--green`) statt fester Farben und
  schalten im Contao-5-Dark-Mode korrekt mit.
- **Marker-Links (Sicherheit):** `Marker` bereinigt jeden Link zentral; erlaubt sind leer,
  `http(s)`/`mailto`/`tel` sowie relative URLs ohne Schema. Alles andere (z. B. `javascript:`,
  `data:`, `vbscript:`, protokoll-relative `//`-Links, Backslashes und Steuerzeichen) wird verworfen. `maplibre.js` prüft
  `link.href` beim Popup-Aufbau zusätzlich als zweite Schicht für fremde JSON-Quellen.
- **Nominatim-Antwort:** `GeocodingService` akzeptiert `lat`/`lon` nur noch, wenn beide numerisch,
  endlich und im gültigen Wertebereich liegen (nicht 0/0); sonst `null` statt fehlerhafter Koordinaten.
- **Nominatim-Drosselung zentralisiert:** `GeocodingService::geocode()` hält jetzt selbst (prozess-
  übergreifend über eine Sperrdatei in `var/` der Installation) den Mindestabstand von 1 Anfrage/Sekunde ein; der bisherige
  `usleep` im Ad-hoc-Marker-Listener entfällt.
- **Adressänderung bei Standorten:** Ein neuer Fingerabdruck (`maplibre_geocoded_address`) erkennt,
  ob sich die Adresse seit dem letzten Geocoding geändert hat, und stößt dann automatisch ein neues
  Geocoding an. Manuell gesetzte Koordinaten ohne Fingerabdruck (Altbestand, KML-Import) bleiben
  unangetastet.
- **Koordinaten-Grenzen:** ungültige, nicht-numerische oder außerhalb des gültigen Bereichs liegende
  Werte (Standorte, Ad-hoc-Marker-Cache, KML-Import) führen jetzt zum Überspringen des Markers statt
  zu stillschweigend `0`.
- **KML-Import:** übernimmt nur noch Punktkoordinaten (`Point/coordinates`); Linien und Flächen
  innerhalb eines Placemarks werden übersprungen statt fehlerhaft interpretiert.
- **JavaScript-Robustheit:** Icon-URL in der CSS-Maske wird kodiert/maskiert (kein Ausbruch aus
  `url("…")`), ein abgebrochener `getClusterExpansionZoom`-Aufruf wird abgefangen, Zoomstufe `0`
  wird nicht mehr fälschlich durch den Fallback ersetzt, und mehrere Marker ohne `fitBounds`/festen
  Mittelpunkt zentrieren jetzt auf die Mitte der Marker statt auf `[0, 0]`.

## [0.1.0] – noch nicht veröffentlicht

### Hinzugefügt
- Backend-Modul **MapLibre Standorte** (`tl_maplibre_location`): Titel, Adresse, Kategorie,
  Verknüpfung, Veröffentlichungs-Status und Anzeige-Zeitraum.
- Automatisches **Geocoding** der Adresse beim Speichern (Nominatim/OpenStreetMap), Koordinaten
  werden gecacht und sind manuell überschreibbar.
- Inhaltselement **MapLibre Karte**: Marker aus zentralen Standorten, Kategorien und/oder Ad-hoc
  (Bezeichnung; Adresse). Optionen für Stil, Höhe, Markerfarbe, Cluster, „sofort interaktiv",
  automatisches Einpassen (`fitBounds`) sowie fester Mittelpunkt/Zoom.
- **Multi-Marker** und **Cluster-Marker** (nummerierte Kreise, Klick zoomt in die Region).
- Optik nach Vorbild von OpenFreeMap (`bright`/`liberty`/`positron`), Marker-Standardfarbe `#4a6b3a`,
  „Zum Aktivieren klicken".
- Konsolen-Befehl `contao:maplibre:import-mymaps` zum Import bestehender „Google My Maps"-Karten (KML).
- Automatische **CSP**-Quellen-Registrierung auf Contao 5.x.
- Wiederverwendbare Render-Primitive (`Marker`, `MaplibreRenderer`) für eigene Bundles.
- Deutsch- und englischsprachige Backend-Texte.

### Kompatibilität
- Contao 4.13, 5.3 und 5.7 LTS (PHP ≥ 8.1).
