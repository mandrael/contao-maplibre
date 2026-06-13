# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden hier dokumentiert.
Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).

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
