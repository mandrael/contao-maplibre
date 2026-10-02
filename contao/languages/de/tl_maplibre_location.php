<?php

// Legenden
$GLOBALS['TL_LANG']['tl_maplibre_location']['location_legend'] = 'Standort';
$GLOBALS['TL_LANG']['tl_maplibre_location']['address_legend']  = 'Adresse';
$GLOBALS['TL_LANG']['tl_maplibre_location']['coords_legend']   = 'Koordinaten';
$GLOBALS['TL_LANG']['tl_maplibre_location']['marker_legend']   = 'Marker-Symbol';
$GLOBALS['TL_LANG']['tl_maplibre_location']['link_legend']     = 'Verknüpfung';
$GLOBALS['TL_LANG']['tl_maplibre_location']['publish_legend']  = 'Veröffentlichung';

// Felder
$GLOBALS['TL_LANG']['tl_maplibre_location']['title']              = ['Titel', 'Name des Standorts (erscheint im Popup).'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['description']        = ['Beschreibung', 'Kurzer Text im Popup, z. B. Öffnungszeiten oder Ruhetag.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['category']           = ['Kategorie', 'Frei wählbare Kategorie zur Gruppierung (z. B. „Kursorte").'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['street']             = ['Straße und Hausnummer', 'Grundlage für die automatische Koordinaten-Ermittlung.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['postal']             = ['PLZ', ''];
$GLOBALS['TL_LANG']['tl_maplibre_location']['city']               = ['Ort', ''];
$GLOBALS['TL_LANG']['tl_maplibre_location']['country']            = ['Land', 'Optional – verbessert die Genauigkeit der Adresssuche.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['maplibre_regeocode'] = ['Koordinaten neu aus Adresse ermitteln', 'Beim Speichern die Koordinaten anhand der Adresse neu berechnen (überschreibt manuell gesetzte Werte).'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['latitude']           = ['Breitengrad', 'Wird aus der Adresse ermittelt, kann manuell überschrieben werden.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['longitude']          = ['Längengrad', 'Wird aus der Adresse ermittelt, kann manuell überschrieben werden.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['icon']               = ['Marker-Symbol', 'Symbol für diesen Standort (leer = Standard-Pin). Überschreibt das Gruppen-Standardsymbol des Karten-Elements.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['iconSvg']            = ['Eigenes SVG-Symbol', 'Optional: eine eigene SVG-Datei als Marker-Symbol. Hat Vorrang vor dem ausgewählten Symbol.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['link']               = ['Verknüpfung (URL)', 'Optionaler Link im Popup, z. B. zur Detailseite.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['published']          = ['Standort veröffentlichen', 'Nur veröffentlichte Standorte erscheinen auf den Karten.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['start']              = ['Anzeigen ab', 'Standort erst ab diesem Zeitpunkt anzeigen.'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['stop']               = ['Anzeigen bis', 'Standort nur bis zu diesem Zeitpunkt anzeigen.'];

// Operationen
$GLOBALS['TL_LANG']['tl_maplibre_location']['new']    = ['Neuer Standort', 'Einen neuen Standort anlegen'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['edit']   = ['Bearbeiten', 'Standort ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['copy']   = ['Duplizieren', 'Standort ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['delete'] = ['Löschen', 'Standort ID %s löschen'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['toggle'] = ['Veröffentlichen', 'Standort ID %s veröffentlichen/verbergen'];
$GLOBALS['TL_LANG']['tl_maplibre_location']['show']   = ['Details', 'Standort ID %s anzeigen'];

// Meldungen
$GLOBALS['TL_LANG']['tl_maplibre_location']['geocodeError'] = 'Geocoding fehlgeschlagen. Bitte Koordinaten manuell eintragen.';
$GLOBALS['TL_LANG']['tl_maplibre_location']['geocodeOk']    = 'Koordinaten ermittelt: %s, %s';

// Tooltips der Statuspunkte in der Liste
$GLOBALS['TL_LANG']['tl_maplibre_location']['coordsAvailable'] = 'Koordinaten vorhanden';
$GLOBALS['TL_LANG']['tl_maplibre_location']['coordsMissing']   = 'Keine Koordinaten';
