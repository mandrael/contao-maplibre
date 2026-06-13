<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\EventListener;

use Contao\DataContainer;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Mandrael\ContaoMaplibreBundle\Service\GeocodingService;

/**
 * onsubmit_callback für tl_maplibre_location: ermittelt beim Speichern aus der Adresse die
 * Koordinaten (Nominatim) und cacht sie in der DB. Geocodiert nur, wenn Koordinaten fehlen oder
 * "neu ermitteln" angehakt ist – manuell gesetzte Koordinaten bleiben sonst unangetastet.
 */
final class LocationGeocodeListener
{
    public function __construct(
        private readonly GeocodingService $geocoder,
        private readonly Connection $connection,
    ) {
    }

    public function onSubmit(DataContainer $dc): void
    {
        $id = (int) ($dc->id ?? 0);

        if (0 === $id) {
            return;
        }

        $row = $this->connection->fetchAssociative('SELECT * FROM tl_maplibre_location WHERE id = ?', [$id]);

        if (false === $row) {
            return;
        }

        $address = $this->composeAddress($row);

        if ('' === $address) {
            return;
        }

        $hasCoords = '' !== (string) ($row['latitude'] ?? '') && '' !== (string) ($row['longitude'] ?? '');
        $regeocode = '1' === (string) ($row['maplibre_regeocode'] ?? '');

        if ($hasCoords && !$regeocode) {
            return;
        }

        $coords = $this->geocoder->geocode($address);

        if (null === $coords) {
            Message::addError($GLOBALS['TL_LANG']['tl_maplibre_location']['geocodeError'] ?? 'Geocoding fehlgeschlagen. Bitte Koordinaten manuell eintragen.');

            if ($regeocode) {
                $this->connection->update('tl_maplibre_location', ['maplibre_regeocode' => ''], ['id' => $id]);
            }

            return;
        }

        $this->connection->update('tl_maplibre_location', [
            'latitude' => (string) $coords['lat'],
            'longitude' => (string) $coords['lng'],
            'maplibre_regeocode' => '',
        ], ['id' => $id]);

        Message::addConfirmation(sprintf(
            $GLOBALS['TL_LANG']['tl_maplibre_location']['geocodeOk'] ?? 'Koordinaten ermittelt: %s, %s',
            $coords['lat'],
            $coords['lng']
        ));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function composeAddress(array $row): string
    {
        $street = trim((string) ($row['street'] ?? ''));
        $cityLine = trim(trim((string) ($row['postal'] ?? '')).' '.trim((string) ($row['city'] ?? '')));
        $country = trim((string) ($row['country'] ?? ''));

        return implode(', ', array_filter([$street, $cityLine, $country]));
    }
}
