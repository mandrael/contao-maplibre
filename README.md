# Contao MapLibre

<img src="logo.svg" alt="Contao MapLibre" width="88" align="right">

**Deutsch** | [English](README.en.md)

Datenschutzfreundliche Karten für Contao mit [MapLibre GL JS](https://maplibre.org/) und
[OpenFreeMap](https://openfreemap.org/) (Vektor-Tiles auf Basis von OpenMapTiles / OpenStreetMap) –
ein direkter Ersatz für Google Maps. Mit **Multi-Marker**, **Cluster-Markern**, zentraler
Standortverwaltung und automatischem **Geocoding** (Adresse → Koordinaten via Nominatim).

Eine gemeinsame Codebasis für die drei Contao-LTS-Versionen **4.13, 5.3 und 5.7** (inkl. 5.4–5.6).

---

## Funktionsumfang

- **Inhaltselement „MapLibre Karte"** – platzierbar in jedem Artikel, ersetzt eine Google-Map dort,
  wo sie steht.
- **Backend-Modul „MapLibre Standorte"** (`tl_maplibre_location`) – zentrale Verwaltung von Orten mit
  Titel, Adresse, Kategorie, Verknüpfung und Veröffentlichungs-Status.
- **Automatik statt Pflege:** Ein Standort, der **unveröffentlicht** wird, verschwindet automatisch
  von **allen** Karten. Kein manuelles Entfernen einzelner Marker.
- **Geocoding beim Speichern:** Aus der Adresse werden per Nominatim (OpenStreetMap) die Koordinaten
  ermittelt und in der Datenbank gecacht – manuelle Koordinaten bleiben möglich.
- **Multi-Marker & Cluster:** Beliebig viele Marker pro Karte; optional als zusammenfassende,
  nummerierte Cluster-Kreise, die beim Klick in die Region zoomen.
- **Optik wie gewohnt:** OpenFreeMap-Stil `bright` (alternativ `liberty`/`positron`), dezente
  Marker-Farbe, „Zum Aktivieren klicken" (die Seite scrollt durch die Karte hindurch, bis man sie
  anklickt).
- **Migration aus Google My Maps:** Ein Konsolen-Befehl importiert die Marker einer bestehenden
  „Google My Maps"-Karte (KML) als Standorte.
- **Wiederverwendbar:** Die Render-Logik ist als Service (`MaplibreRenderer`) gekapselt und kann von
  eigenen Bundles genutzt werden.

## Datenschutz

Es werden **keine** Google-Dienste eingebunden. Die Tiles kommen von OpenFreeMap, MapLibre GL JS von
unpkg. Es findet kein Tracking statt. Geocoding läuft ausschließlich **im Backend beim Speichern**
(nicht im Frontend), die Koordinaten werden gecacht.

## Installation

```bash
composer require mandrael/contao-maplibre
vendor/bin/contao-console cache:clear
vendor/bin/contao-console contao:migrate
```

Alternativ über den **Contao Manager** nach `mandrael/contao-maplibre` suchen, hinzufügen und die
Datenbank aktualisieren.

## Verwendung

### 1. Standorte anlegen

Im Backend unter **Inhalte → MapLibre Standorte** einen Standort anlegen: Titel, Adresse
(Straße, PLZ, Ort, optional Land) und eine **Kategorie** (z. B. „Kursorte"). Beim Speichern werden
die **Koordinaten automatisch aus der Adresse ermittelt** (Nominatim) und gespeichert.

- Koordinaten lassen sich jederzeit **manuell überschreiben**.
- „Koordinaten neu aus Adresse ermitteln" erzwingt beim nächsten Speichern eine Neuberechnung.
- Nur **veröffentlichte** Standorte (innerhalb des optionalen Anzeige-Zeitraums) erscheinen auf Karten.

### 2. Karte einfügen

Im Artikel ein Inhaltselement vom Typ **MapLibre Karte** hinzufügen und die Marker-Quelle wählen:

- **Standorte** – einzelne zentrale Standorte, und/oder
- **Kategorien** – alle veröffentlichten Standorte der gewählten Kategorien, und/oder
- **Eigene Marker (Ad-hoc)** – eine Zeile je Marker im Format `Bezeichnung; Adresse`. Die Koordinaten
  werden beim Speichern des Elements automatisch ermittelt und gecacht.

Darstellungs-Optionen: Kartenstil, Höhe, Markerfarbe, **Cluster** an/aus, „sofort interaktiv".
Ausschnitt: „alle Marker automatisch einpassen" (`fitBounds`) **oder** fester Mittelpunkt + Zoomstufe.

### 3. Bestehende Google-My-Maps-Karte importieren

```bash
vendor/bin/contao-console contao:maplibre:import-mymaps <mid> --category=Kursorte
```

Die `mid` steht in der Embed-/Teilen-URL der Google-My-Maps-Karte
(`https://www.google.com/maps/d/embed?mid=<mid>`). Die enthaltenen Marker (Name + Koordinaten) werden
als Standorte der angegebenen Kategorie angelegt. Adressen kannst du anschließend im Backend ergänzen.

## Content Security Policy (CSP)

Nutzt die Seite eine CSP, müssen folgende Quellen erlaubt sein:

```
script-src  https://unpkg.com;
style-src   https://unpkg.com;
connect-src https://tiles.openfreemap.org;
img-src     data: blob:;
worker-src  blob:;
```

Auf **Contao 5.x** werden diese Quellen **automatisch** in die Seiten-CSP eingetragen, sofern die
Seite eine CSP gesetzt hat. Auf Contao 4.13 sind sie ggf. manuell zu ergänzen.

## Wiederverwendung in eigenen Bundles

Die Karte lässt sich programmatisch erzeugen – z. B. aus einem eigenen Katalog-/Personen-Bundle:

```php
use Mandrael\ContaoMaplibreBundle\Map\Marker;
use Mandrael\ContaoMaplibreBundle\Map\MaplibreRenderer;

$html = $renderer->render(
    [
        new Marker(47.8127, 13.0489, 'Praxis Tobar', 'Breitenfelderstr. 47a, 5020 Salzburg', '/profil/tobar'),
        // ...
    ],
    ['cluster' => true, 'height' => 500]
);
```

`$renderer` ist der Service `Mandrael\ContaoMaplibreBundle\Map\MaplibreRenderer` (autowire-fähig).

## Kompatibilität

- **PHP:** 8.1+
- **Contao:** 4.13 LTS, 5.3 LTS und 5.7 LTS (inkl. 5.4–5.6) aus einer Codebasis.

## Attribution

Die geforderte Quellenangabe „MapLibre | OpenFreeMap © OpenMapTiles Data from OpenStreetMap" wird vom
OpenFreeMap-Stil automatisch in der Karte angezeigt.

## Lizenz

MIT – siehe [LICENSE](LICENSE).
