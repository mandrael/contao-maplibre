# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden hier dokumentiert.
Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).

## [0.2.0] – noch nicht veröffentlicht

### Hinzugefügt
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
- **Kartenhöhe bei aktiver CSP:** wird jetzt per Skript (CSSOM) statt als Inline-`style`-Attribut
  gesetzt, damit die Karte sichtbar bleibt, wenn `style-src` kein `'unsafe-inline'` erlaubt.
- **Backend-Dark-Mode:** Status-Punkte und Ort-Suffix der Standort-Liste sowie der
  Icon-Picker nutzen Contao-CSS-Klassen/-Variablen (`tl_gray`/`tl_green`/`tl_red`,
  `--form-bg`/`--form-border`/`--text`/`--gray`/`--green`) statt fester Farben und
  schalten im Contao-5-Dark-Mode korrekt mit.

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
